<?php

namespace Tests\Feature;

use App\Enums\BaseItemCategory;
use App\Models\BaseItem;
use App\Models\DynamicModel;
use App\Models\Shop;
use App\Models\WorldTemplate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateRetroCardShopsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();
        config()->set('database.connections.retro_local', config('database.connections.'.config('database.default')));
        WorldTemplate::query()->create([
            'name' => 'Retro Local', 'slug' => 'retro_local', 'connection_name' => 'retro_local',
            'remote_database_server' => 'local', 'database_name' => 'testing', 'is_active' => true, 'is_visible' => true,
        ]);
        Schema::create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['2024_12_18_142617_create_shops_table.php', '2024_12_18_143413_create_shop_items_table.php', '2025_07_28_215521_add_binds_items_permanently_to_shops_table.php', '2025_09_26_170701_add_price_columns_to_shops_table.php', '2025_09_27_000000_add_currency_item_id_to_shops_table.php'] as $migration) {
            (require database_path('migrations/remote/'.$migration))->up();
        }
    }

    protected function tearDown(): void
    {
        $this->app->detectEnvironment(fn (): string => 'testing');
        Schema::dropIfExists('shop_items');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('base_items');
        DynamicModel::clearGlobalConnection();
        DB::purge('retro_local');
        parent::tearDown();
    }

    public function test_creates_all_cards_with_catalog_size_and_is_idempotent(): void
    {
        $catalog = json_decode(file_get_contents(resource_path('retro-playing-cards.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($catalog as $entry) {
            $this->card($entry['card']);
        }
        $this->card(['deck' => 'other']);
        $deleted = $this->card(['deck' => 'retro']);
        $deleted->delete();
        $this->artisan('card-game:create-retro-shops')->expectsOutputToContain('Konsola gry: shop ')->assertSuccessful();
        $this->artisan('card-game:create-retro-shops')->assertSuccessful();
        $this->assertSame(1, Shop::on('retro_local')->count());
        $shop = Shop::on('retro_local')->firstOrFail();
        $this->assertSame(count($catalog), $shop->items()->count());
        $this->assertEquals(range(0, count($catalog) - 1), $shop->items()->orderByPivot('position')->pluck('shop_items.position')->all());
        $this->assertFalse($shop->binds_items_permanently);
    }

    public function test_overflow_creates_more_shops_and_incremental_cards_fill_available_slots(): void
    {
        foreach (range(1, 81) as $index) {
            $this->card(['deck' => 'retro']);
        }
        $this->artisan('card-game:create-retro-shops')->assertSuccessful();
        $shops = Shop::on('retro_local')->withCount('items')->orderBy('id')->get();
        $this->assertEquals([80, 1], $shops->pluck('items_count')->all());
        $this->card(['deck' => 'retro']);
        $this->artisan('card-game:create-retro-shops')->assertSuccessful();
        $this->assertEquals([80, 2], Shop::on('retro_local')->withCount('items')->orderBy('id')->pluck('items_count')->all());
        $this->assertSame(82, DB::table('shop_items')->distinct()->count('item_id'));
    }

    public function test_missing_cards_and_non_local_environment_do_not_create_shops(): void
    {
        $this->artisan('card-game:create-retro-shops')->expectsOutputToContain('Brak kart Retro')->assertFailed();
        $this->assertSame(0, Shop::on('retro_local')->count());
        $this->app->detectEnvironment(fn (): string => 'production');
        $this->artisan('card-game:create-retro-shops')->expectsOutputToContain('wyłącznie lokalnie')->assertFailed();
        $this->assertSame(0, Shop::on('retro_local')->count());
    }

    private function card(array $definition): BaseItem
    {
        return BaseItem::on('retro_local')->create(['name' => 'Karta', 'category' => BaseItemCategory::PLAYING_CARDS, 'attributes' => ['playingCard' => $definition]]);
    }
}
