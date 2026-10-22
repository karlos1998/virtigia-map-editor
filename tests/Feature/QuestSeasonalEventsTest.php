<?php

namespace Tests\Feature;

use App\Http\Resources\QuestResource;
use App\Models\DynamicModel;
use App\Models\Quest;
use App\Models\SeasonalEvent;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\WorldTemplateSnapshotService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuestSeasonalEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => $connection]);
        DynamicModel::setGlobalConnection($connection);

        Schema::create('quests', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('seasonal_events', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        $migration = require database_path('migrations/remote/2026_10_22_143757_create_quest_seasonal_events_table.php');
        $migration->up();

        $this->actingAs(User::factory()->create([
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]))->withSession(['world' => 'retro']);
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_it_attaches_replaces_and_clears_optional_quest_events(): void
    {
        $quest = Quest::query()->create(['name' => 'Sezonowa wyprawa']);
        $first = $this->event('Zima');
        $second = $this->event('Lato');

        $this->assertCount(0, $quest->seasonalEvents);

        foreach ([[$first->id, $second->id], [$second->id], []] as $ids) {
            $this->patch(route('quests.seasonal-events.sync', $quest), ['seasonal_event_ids' => $ids])
                ->assertRedirect(route('quests.show', $quest));

            $this->assertEqualsCanonicalizing($ids, $quest->fresh()->seasonalEvents->modelKeys());
            $this->assertSame('Sezonowa wyprawa', $quest->fresh()->name);
        }
    }

    public function test_it_rejects_invalid_and_duplicate_event_ids_without_removing_existing_links(): void
    {
        $quest = Quest::query()->create(['name' => 'Wyprawa']);
        $event = $this->event('Zima');
        $quest->seasonalEvents()->attach($event);

        foreach ([[999999], [$event->id, $event->id], ['invalid']] as $ids) {
            $this->patch(route('quests.seasonal-events.sync', $quest), ['seasonal_event_ids' => $ids])
                ->assertSessionHasErrors('seasonal_event_ids.0');

            $this->assertSame([$event->id], $quest->fresh()->seasonalEvents->modelKeys());
        }

        $this->patch(route('quests.seasonal-events.sync', $quest), [])
            ->assertSessionHasErrors('seasonal_event_ids');
        $this->assertSame([$event->id], $quest->fresh()->seasonalEvents->modelKeys());
    }

    public function test_it_cascades_event_and_quest_deletion_and_can_roll_back_the_pivot(): void
    {
        $quest = Quest::query()->create(['name' => 'Wyprawa']);
        $event = $this->event('Zima');
        $quest->seasonalEvents()->attach($event);
        $event->delete();
        $this->assertDatabaseCount('quest_seasonal_events', 0);

        $event = $this->event('Lato');
        $quest->seasonalEvents()->attach($event);
        $quest->delete();
        $this->assertDatabaseCount('quest_seasonal_events', 0);

        $migration = require database_path('migrations/remote/2026_10_22_143757_create_quest_seasonal_events_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('quest_seasonal_events'));
    }

    public function test_editor_resource_exposes_assigned_events_and_their_current_state(): void
    {
        $quest = \Mockery::mock(Quest::class)->makePartial();
        $quest->forceFill(['id' => 1, 'name' => 'Wyprawa']);
        $quest->setRelation('steps', new Collection);
        $active = $this->event('Zima');
        $inactive = $this->event('Lato');
        $inactive->update(['active' => false]);
        $quest->setRelation('seasonalEvents', new Collection([$active, $inactive]));
        foreach (['getDialogs', 'getNodes', 'getNodeOptions', 'getEdges'] as $method) {
            $quest->shouldReceive($method)->once()->andReturn(collect());
        }

        $data = (new QuestResource($quest))->resolve(Request::create('/'));

        $this->assertSame([
            ['id' => $active->id, 'name' => 'Zima', 'is_currently_active' => true],
            ['id' => $inactive->id, 'name' => 'Lato', 'is_currently_active' => false],
        ], $data['seasonal_events']->all());
    }

    public function test_quest_event_links_are_included_in_world_template_updates(): void
    {
        $this->assertContains('quest_seasonal_events', config('world_templates.update_tables'));
        config()->set('world_templates.update_tables', ['quest_seasonal_events']);

        $manifest = app(WorldTemplateSnapshotService::class)->schema((new Quest)->getConnection());

        $this->assertSame(['quest_id', 'seasonal_event_id'], $manifest['quest_seasonal_events']['keys']);
    }

    private function event(string $name): SeasonalEvent
    {
        return SeasonalEvent::query()->create(['name' => $name, 'slug' => $name, 'active' => true]);
    }
}
