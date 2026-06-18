<?php

namespace Tests\Feature;

use App\Models\DynamicModel;
use App\Models\User;
use App\Services\BaseItemDuplicateViewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BaseItemDuplicateViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        config()->set('database.connections.retro', [
            'driver' => 'sqlite',
            'database' => database_path('testing-duplicates-retro.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (! file_exists(database_path('testing-duplicates-retro.sqlite'))) {
            touch(database_path('testing-duplicates-retro.sqlite'));
        }

        $this->createWorldSchema('retro');
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_it_refreshes_and_displays_potential_duplicate_base_items(): void
    {
        $this->seedBaseItems('retro');

        $rows = app(BaseItemDuplicateViewService::class)->refresh('retro');

        $this->assertSame(1, $rows);
        $this->assertDatabaseHas('base_item_duplicate_views', [
            'duplicate_base_item_id' => 11,
            'used_base_item_id' => 10,
            'name' => 'Hełm Fistuły',
            'category' => 'helmets',
            'rarity' => 'unique',
            'need_level' => 35,
        ], 'retro');

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('base-items.duplicates.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('BaseItem/Duplicates')
            ->has('duplicates.data', 1)
            ->where('duplicates.data.0.duplicate_item.id', 11)
            ->where('duplicates.data.0.used_item.id', 10)
            ->where('duplicates.data.0.need_level', 35));
    }

    private function createWorldSchema(string $connection): void
    {
        $schema = Schema::connection($connection);

        $schema->disableForeignKeyConstraints();
        $schema->dropIfExists('base_item_duplicate_views');
        $schema->dropIfExists('base_item_usage_views');
        $schema->dropIfExists('base_items');
        $schema->enableForeignKeyConstraints();

        $schema->create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->text('stats')->default('');
            $table->unsignedInteger('cl')->default(0);
            $table->unsignedInteger('pr')->default(0);
            $table->boolean('edited_manually')->default(false);
            $table->json('attributes')->nullable();
            $table->json('attribute_points')->nullable();
            $table->json('manual_attribute_points')->nullable();
            $table->json('reverse_attributes')->nullable();
            $table->string('rarity')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->string('currency')->nullable();
            $table->unsignedInteger('specific_currency_price')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('base_item_usage_views', function (Blueprint $table): void {
            $table->unsignedBigInteger('base_item_id')->primary();
            $table->boolean('is_in_use')->default(false);
            $table->unsignedInteger('source_count')->default(0);
            $table->json('sources')->nullable();
            $table->timestamps();
        });

        $schema->create('base_item_duplicate_views', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('duplicate_base_item_id');
            $table->unsignedBigInteger('used_base_item_id');
            $table->char('duplicate_group_hash', 40);
            $table->string('normalized_name', 191);
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('rarity')->nullable();
            $table->unsignedInteger('need_level')->nullable();
            $table->string('duplicate_src');
            $table->string('used_src');
            $table->unsignedInteger('duplicate_usage_source_count')->default(0);
            $table->unsignedInteger('used_usage_source_count')->default(0);
            $table->timestamp('refreshed_at');
            $table->timestamps();
        });
    }

    private function seedBaseItems(string $connection): void
    {
        DB::connection($connection)->table('base_items')->insert([
            $this->baseItemPayload(10, 'Hełm Fistuły', 'helmets', 'unique', 35, 'items/used.gif'),
            $this->baseItemPayload(11, ' Hełm   Fistuły ', 'helmets', 'unique', 35, 'items/duplicate.gif'),
            $this->baseItemPayload(12, 'Hełm Fistuły', 'helmets', 'unique', 36, 'items/other-level.gif'),
            $this->baseItemPayload(13, 'Hełm Fistuły', 'helmets', 'common', 35, 'items/other-rarity.gif'),
        ]);

        DB::connection($connection)->table('base_item_usage_views')->insert([
            $this->usagePayload(10, true, 2),
            $this->usagePayload(11, false, 0),
            $this->usagePayload(12, false, 0),
            $this->usagePayload(13, false, 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseItemPayload(
        int $id,
        string $name,
        string $category,
        string $rarity,
        int $needLevel,
        string $src,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'src' => $src,
            'stats' => '',
            'cl' => 0,
            'pr' => 0,
            'edited_manually' => false,
            'attributes' => json_encode(['needLevel' => $needLevel]),
            'attribute_points' => null,
            'manual_attribute_points' => null,
            'reverse_attributes' => null,
            'rarity' => $rarity,
            'category' => $category,
            'price' => null,
            'currency' => null,
            'specific_currency_price' => null,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function usagePayload(int $baseItemId, bool $isInUse, int $sourceCount): array
    {
        return [
            'base_item_id' => $baseItemId,
            'is_in_use' => $isInUse,
            'source_count' => $sourceCount,
            'sources' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'base-item-duplicate-viewer',
            'name' => 'Base Item Duplicate Viewer',
            'email' => 'base-item-duplicate-viewer@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }
}
