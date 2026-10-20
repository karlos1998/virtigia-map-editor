<?php

namespace Tests\Feature;

use App\Models\DynamicModel;
use App\Services\BaseItemService;
use App\Services\BaseNpcService;
use App\Services\MapService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GraphicFilenameFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DynamicModel::setGlobalConnection(config('database.default'));
        Cache::put('respawn_points_data', []);

        foreach (['base_npcs', 'base_items', 'maps'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->string('name');
                $table->string('src')->nullable();
                $table->timestamps();

                if ($tableName === 'base_items') {
                    $table->softDeletes();
                }

                if ($tableName === 'base_npcs') {
                    $table->string('rank')->default('NORMAL');
                    $table->string('category')->default('MOB');
                    $table->string('profession')->default('w');
                    $table->string('type')->default('npc');
                    $table->boolean('is_aggressive')->default(false);
                }
            });

            foreach (['retro/new/nazwa.gif', 'retro/other.gif', null] as $index => $src) {
                DB::table($tableName)->insert([
                    'id' => $index + 1,
                    'name' => 'Record '.($index + 1),
                    'src' => $src,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::create('base_item_usage_views', function (Blueprint $table): void {
            $table->unsignedBigInteger('base_item_id')->primary();
        });
    }

    protected function tearDown(): void
    {
        foreach (['base_item_usage_views', 'base_npcs', 'base_items', 'maps'] as $tableName) {
            Schema::dropIfExists($tableName);
        }

        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    /**
     * @param  class-string<BaseNpcService|BaseItemService|MapService>  $serviceClass
     * @param  array<int, int>  $expectedIds
     */
    #[DataProvider('graphicFilters')]
    public function test_it_filters_graphics_before_pagination(
        string $serviceClass,
        string $field,
        ?string $search,
        bool $global,
        array $expectedIds,
    ): void {
        request()->merge([
            'tables' => [
                'default' => [
                    'page' => 1,
                    'globalFilter' => $global ? $search : '',
                    'filters' => json_encode($global ? [] : [
                        $field => [
                            'operator' => 'and',
                            'constraints' => [
                                ['value' => $search, 'matchMode' => 'contains'],
                            ],
                        ],
                    ]),
                    'sortField' => 'id',
                    'sortOrder' => 'ASC',
                ],
            ],
        ]);

        $result = app($serviceClass)->getAll();

        $this->assertSame($expectedIds, $result->resource->pluck('id')->all());
        $this->assertSame(count($expectedIds), $result->resource->total());
        $this->assertArrayHasKey($field, $result->additional['tableData']->columns);
    }

    public static function graphicFilters(): iterable
    {
        foreach ([
            'NPCs' => [BaseNpcService::class, 'src'],
            'items' => [BaseItemService::class, 'src'],
            'maps' => [MapService::class, 'path'],
        ] as $name => [$serviceClass, $field]) {
            yield "$name filename" => [$serviceClass, $field, 'nazwa.gif', false, [1]];
            yield "$name partial filename" => [$serviceClass, $field, 'nazwa', false, [1]];
            yield "$name no match" => [$serviceClass, $field, 'missing.gif', false, []];
            yield "$name cleared filter" => [$serviceClass, $field, null, false, [1, 2, 3]];
            yield "$name global search" => [$serviceClass, $field, 'nazwa.gif', true, [1]];
        }
    }
}
