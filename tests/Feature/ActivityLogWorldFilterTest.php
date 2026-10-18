<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorldTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogWorldFilterTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('worldSelections')]
    public function test_it_filters_activity_logs_by_available_world_templates(mixed $selection, array $expectedWorlds): void
    {
        WorldTemplate::query()->update(['is_visible' => false]);

        foreach (['retro', 'classic', 'hidden', 'inactive'] as $slug) {
            WorldTemplate::query()->updateOrCreate(['slug' => $slug], [
                'name' => ucfirst($slug),
                'connection_name' => $slug,
                'remote_database_server' => 'test',
                'database_name' => 'filter_test_'.$slug,
                'is_active' => $slug !== 'inactive',
                'is_visible' => $slug !== 'hidden',
            ]);

            Activity::query()->create([
                'log_name' => 'default',
                'description' => 'updated',
                'event' => 'updated',
                'properties' => [],
                'world' => $slug,
            ]);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('activity-logs.index', [
                'tables' => ['default' => ['filters' => json_encode([
                    'world' => [
                        'operator' => 'and',
                        'constraints' => [['value' => $selection, 'matchMode' => 'contains']],
                    ],
                ])]],
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('ActivityLog/Index')
                ->has('logs.data', count($expectedWorlds))
                ->where('logs.data', fn ($logs): bool => collect($logs)->pluck('world')->sort()->values()->all() === $expectedWorlds)
                ->where('logs.tableData.columns.world.options', [
                    ['value' => 'retro', 'label' => 'Retro'],
                    ['value' => 'classic', 'label' => 'Classic'],
                ])
                ->where('logs.tableData.activeFilters.world.constraints.0.value', $selection));
    }

    public static function worldSelections(): array
    {
        return [
            'no selection' => [null, ['classic', 'hidden', 'inactive', 'retro']],
            'cleared selection' => [[], ['classic', 'hidden', 'inactive', 'retro']],
            'one world' => [['retro'], ['retro']],
            'multiple worlds' => [['retro', 'classic'], ['classic', 'retro']],
            'unavailable worlds' => [['hidden', 'inactive', 'missing'], []],
            'mixed worlds' => [['retro', 'missing'], ['retro']],
            'malformed selection' => ['retro', []],
            'malformed values' => [[['value' => 'retro'], 1], []],
        ];
    }

    public function test_guests_cannot_view_activity_logs(): void
    {
        $this->get(route('activity-logs.index'))->assertRedirect();
    }
}
