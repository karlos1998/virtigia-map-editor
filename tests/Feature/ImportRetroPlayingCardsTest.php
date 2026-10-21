<?php

namespace Tests\Feature;

use App\Models\BaseItem;
use App\Models\DynamicModel;
use App\Models\User;
use App\Models\WorldTemplate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportRetroPlayingCardsTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Schema::dropIfExists('base_items');
        DynamicModel::clearGlobalConnection();
        DB::purge('retro_local');
        parent::tearDown();
    }

    public function test_dry_run_writes_nothing_and_import_is_idempotent_and_preserves_manual_edits(): void
    {
        $this->prepareImport();

        $this->artisan('card-game:import-retro', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, BaseItem::on('retro_local')->count());
        $this->assertCount(0, Storage::disk('s3')->allFiles());
        $this->artisan('card-game:import-retro')->assertSuccessful();
        $this->assertSame(54, BaseItem::on('retro_local')->count());
        $heroes = BaseItem::on('retro_local')->get()->filter(fn (BaseItem $item): bool => data_get($item->attributes, 'playingCard.strength') === 15);
        $this->assertCount(2, $heroes);
        foreach ($heroes as $hero) {
            $this->assertStringEndsWith('-10.png', data_get($hero->attributes, 'playingCard.artwork.outfitSrc'));
        }
        $this->assertCount(54, Storage::disk('s3')->allFiles('img/playing-cards/thumbnails'));
        $item = BaseItem::on('retro_local')->firstOrFail();
        $item->update(['name' => 'Własna nazwa', 'src' => 'custom/thumbnail.png']);

        $this->artisan('card-game:import-retro')->assertSuccessful();

        $this->assertSame(54, BaseItem::on('retro_local')->count());
        $this->assertSame('Własna nazwa', $item->fresh()->name);
        $this->assertSame('custom/thumbnail.png', $item->fresh()->src);
        $this->assertCount(54, Storage::disk('s3')->allFiles('img/playing-cards/thumbnails'));
        $user = User::query()->create([
            'login' => 'card-editor', 'name' => 'Card Editor', 'email' => 'card-editor@example.com',
            'src' => 'retro/avatar.png', 'roles' => ['admin'], 'permissions' => ['world.write'],
        ]);
        $attributes = $item->fresh()->attributes;
        $attributes['description'] = 'Własny opis karty';
        $this->actingAs($user)->withSession(['world' => 'retro_local'])
            ->patch(route('base-items.attributes.update', ['baseItem' => $item->id]), ['attributes' => $attributes])
            ->assertRedirect(route('base-items.show', ['baseItem' => $item->id]));
        $this->assertSame('Własny opis karty', $item->fresh()->attributes['description']);
        $this->assertStringStartsWith('playing-cards/thumbnails/', $item->fresh()->src);
        $this->assertCount(55, Storage::disk('s3')->allFiles('img/playing-cards/thumbnails'));
    }

    public function test_setup_shows_all_missing_artwork_and_writes_no_partial_catalog(): void
    {
        $this->prepareImport(missingOutfits: true);
        config()->set('database.connections.retro_local.username', 'testing');
        $catalog = json_decode(file_get_contents(resource_path('retro-playing-cards.json')), true, flags: JSON_THROW_ON_ERROR);
        $units = array_values(array_filter($catalog, fn (array $entry): bool => $entry['card']['kind'] === 'unit'));

        $this->artisan('card-game:setup-retro')
            ->expectsOutputToContain('Import kart został zatrzymany przez walidację')
            ->expectsOutputToContain($units[0]['label'].': Brak outfitu:')
            ->expectsOutputToContain($units[1]['label'].': Brak outfitu:')
            ->assertFailed();

        $this->assertSame(0, BaseItem::on('retro_local')->count());
        $this->assertCount(0, Storage::disk('s3')->allFiles());
    }

    private function prepareImport(bool $missingOutfits = false): void
    {
        activity()->disableLogging();
        Storage::fake('s3');
        config()->set('database.connections.retro_local', config('database.connections.'.config('database.default')));
        config()->set('services.playing_card_assets.url', 'https://portal.test/api/playing-card-assets');
        WorldTemplate::query()->create([
            'name' => 'Retro Local', 'slug' => 'retro_local', 'connection_name' => 'retro_local',
            'remote_database_server' => 'local', 'database_name' => 'testing',
            'is_active' => true, 'is_visible' => true,
        ]);
        Schema::create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src');
            $table->text('stats');
            $table->integer('cl')->default(0);
            $table->integer('pr')->default(0);
            $table->json('attributes')->nullable();
            $table->json('attribute_points')->nullable();
            $table->json('manual_attribute_points')->nullable();
            $table->string('category')->nullable();
            $table->string('rarity')->nullable();
            $table->string('currency')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('edited_manually')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        $outfits = [];
        foreach (range(0, 10) as $strength) {
            foreach (['w', 'b', 'p', 'm', 'h', 't'] as $profession) {
                $outfits[] = [
                    'profession' => $profession, 'level' => $strength * 10,
                    'type' => $strength === 0 ? 'noob' : 'standard',
                    'src' => "img/outfits/{$profession}-{$strength}.png",
                ];
            }
        }
        Http::fake(['https://portal.test/*' => Http::response([
            'outfits' => $missingOutfits ? [] : $outfits, 'backgrounds' => [['src' => 'https://portal.test/fback/common.png']],
        ])]);

    }
}
