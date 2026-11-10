<?php

namespace Tests\Feature;

use App\Jobs\RefreshQuestWalkthroughsJob;
use App\Models\DynamicModel;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\QuestWalkthrough\QuestWalkthroughService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class QuestWalkthroughTest extends TestCase
{
    use RefreshDatabase;

    private string $connection;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        $this->connection = config('database.default');
        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => $this->connection]);
        DynamicModel::setGlobalConnection($this->connection);

        $this->createTemplateTables();
        (require database_path('migrations/remote/2026_11_10_120000_create_quest_walkthroughs_table.php'))->up();
        $this->seedQuest();

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

    public function test_world_refresh_generates_once_and_skips_when_nothing_changed(): void
    {
        $service = app(QuestWalkthroughService::class);

        $first = $service->refreshWorld($this->connection);
        $this->assertFalse($first['skipped']);
        $this->assertSame(1, $first['changed']);

        $row = DB::table('quest_walkthroughs')->where('quest_id', 1)->first();
        $walkthrough = json_decode($row->walkthrough, true);
        $this->assertSame('complete', $row->status);
        $this->assertSame(['Słucham?', 'Pomogę.'], array_column(array_filter($walkthrough['sections'][0]['timeline'], fn ($e) => $e['type'] === 'choose'), 'label'));
        $this->assertSame(['Karczma'], array_column($walkthrough['sections'][0]['npcs'][0]['locations'], 'map_name'));

        $this->assertSame(['skipped' => true, 'reason' => 'unchanged'], $service->refreshWorld($this->connection));

        // Editing a dialog option changes the source signature and the stored walkthrough.
        DB::table('dialog_node_options')->where('id', 2)->update(['label' => 'Chętnie pomogę.', 'updated_at' => now()->addMinute()]);
        $third = $service->refreshWorld($this->connection);
        $this->assertFalse($third['skipped']);
        $this->assertSame(1, $third['changed']);
        $this->assertStringContainsString('Chętnie pomogę.', DB::table('quest_walkthroughs')->value('walkthrough'));
    }

    public function test_page_shows_the_stored_walkthrough_and_can_regenerate_it(): void
    {
        $this->get(route('quests.walkthrough', 1))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Quest/Walkthrough')
                ->where('quest.id', 1)
                ->where('meta.available', true)
                ->where('meta.live_preview', true)
                ->where('walkthrough.status', 'complete'));

        $this->assertSame(0, DB::table('quest_walkthroughs')->count());

        $this->post(route('quests.walkthrough.regenerate', 1))
            ->assertRedirect(route('quests.walkthrough', 1));

        $this->get(route('quests.walkthrough', 1))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('meta.live_preview', false)
                ->where('walkthrough.summary.required_level', 20)
                ->has('walkthrough.sections', 1));
    }

    public function test_job_is_unique_per_world(): void
    {
        $this->assertSame('retro', (new RefreshQuestWalkthroughsJob('retro'))->uniqueId());
    }

    private function seedQuest(): void
    {
        $now = now();
        DB::table('quests')->insert(['id' => 1, 'name' => 'Pomoc karczmarzowi', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('quest_steps')->insert([
            ['id' => 10, 'quest_id' => 1, 'name' => 'Pomóż karczmarzowi', 'description' => null, 'visible_in_quest_list' => true, 'auto_advance_next_day' => false, 'auto_advance_to_step_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('dialogs')->insert(['id' => 5, 'name' => 'Karczmarz', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('dialog_nodes')->insert([
            ['id' => 1, 'type' => 'start', 'content' => null, 'source_dialog_id' => 5, 'additional_actions' => null, 'action_data' => null, 'shop_id' => null, 'hotel_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'type' => 'special', 'content' => 'Witaj!', 'source_dialog_id' => 5, 'additional_actions' => null, 'action_data' => null, 'shop_id' => null, 'hotel_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'type' => 'special', 'content' => 'Mam zadanie.', 'source_dialog_id' => 5, 'additional_actions' => null, 'action_data' => null, 'shop_id' => null, 'hotel_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'type' => 'special', 'content' => 'Dzięki!', 'source_dialog_id' => 5, 'additional_actions' => json_encode(['setQuestStep' => ['value' => 10]]), 'action_data' => null, 'shop_id' => null, 'hotel_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('dialog_node_options')->insert([
            ['id' => 1, 'node_id' => 2, 'label' => 'Słucham?', 'order' => 0, 'rules' => null, 'additional_action' => null, 'additional_actions' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'node_id' => 3, 'label' => 'Pomogę.', 'order' => 0, 'rules' => json_encode(['level' => ['value' => 20]]), 'additional_action' => null, 'additional_actions' => null, 'created_at' => $now, 'updated_at' => $now],
            // A legacy enum value that the Eloquent cast would reject must not break generation.
            ['id' => 3, 'node_id' => 3, 'label' => 'Daj mi poziomy.', 'order' => 1, 'rules' => null, 'additional_action' => 'ADD_LEVELS', 'additional_actions' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('dialog_edges')->insert([
            ['id' => 1, 'source_dialog_id' => 5, 'source_node_id' => 1, 'source_option_id' => null, 'source_handle' => null, 'target_node_id' => 2, 'rules' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'source_dialog_id' => 5, 'source_node_id' => 2, 'source_option_id' => 1, 'source_handle' => null, 'target_node_id' => 3, 'rules' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'source_dialog_id' => 5, 'source_node_id' => 3, 'source_option_id' => 2, 'source_handle' => null, 'target_node_id' => 4, 'rules' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('maps')->insert([
            ['id' => 7, 'name' => 'Karczma', 'is_internal' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'name' => 'Siedziba MG', 'is_internal' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('base_npcs')->insert(['id' => 3, 'name' => 'Karczmarz', 'lvl' => 10, 'src' => 'npc.gif', 'rank' => null, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('npcs')->insert(['id' => 9, 'dialog_id' => 5, 'base_npc_id' => 3, 'auto_start_dialog' => false, 'enabled' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('npc_locations')->insert([
            ['id' => 1, 'npc_id' => 9, 'map_id' => 8, 'x' => 31, 'y' => 11, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'npc_id' => 9, 'map_id' => 7, 'x' => 6, 'y' => 18, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function createTemplateTables(): void
    {
        $timestamped = function (string $table, callable $columns): void {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
            Schema::create($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->id();
                $columns($blueprint);
                $blueprint->timestamps();
            });
        };

        $timestamped('quests', fn (Blueprint $t) => $t->string('name'));
        $timestamped('quest_steps', function (Blueprint $t): void {
            $t->foreignId('quest_id');
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('visible_in_quest_list')->default(true);
            $t->boolean('auto_advance_next_day')->default(false);
            $t->unsignedBigInteger('auto_advance_to_step_id')->nullable();
        });
        $timestamped('quest_step_auto_progresses', function (Blueprint $t): void {
            $t->foreignId('quest_step_id');
            $t->string('type');
            $t->unsignedInteger('time_seconds')->nullable();
        });
        $timestamped('quest_step_auto_progress_mobs', function (Blueprint $t): void {
            $t->foreignId('quest_step_auto_progress_id');
            $t->unsignedBigInteger('base_npc_id')->nullable();
            $t->unsignedBigInteger('mob_species_id')->nullable();
            $t->unsignedInteger('quantity');
        });
        $timestamped('dialogs', fn (Blueprint $t) => $t->string('name')->nullable());
        $timestamped('dialog_nodes', function (Blueprint $t): void {
            $t->string('type');
            $t->text('content')->nullable();
            $t->unsignedBigInteger('source_dialog_id');
            $t->json('action_data')->nullable();
            $t->json('additional_actions')->nullable();
            $t->unsignedBigInteger('shop_id')->nullable();
            $t->unsignedBigInteger('hotel_id')->nullable();
        });
        $timestamped('dialog_node_options', function (Blueprint $t): void {
            $t->unsignedBigInteger('node_id');
            $t->string('label')->nullable();
            $t->integer('order')->nullable();
            $t->json('rules')->nullable();
            $t->string('additional_action')->nullable();
            $t->json('additional_actions')->nullable();
        });
        $timestamped('dialog_edges', function (Blueprint $t): void {
            $t->unsignedBigInteger('source_dialog_id');
            $t->unsignedBigInteger('source_node_id')->nullable();
            $t->string('source_handle')->nullable();
            $t->unsignedBigInteger('source_option_id')->nullable();
            $t->unsignedBigInteger('target_node_id')->nullable();
            $t->json('rules')->nullable();
        });
        $timestamped('dialog_counters', fn (Blueprint $t) => $t->string('name'));
        $timestamped('npcs', function (Blueprint $t): void {
            $t->unsignedBigInteger('dialog_id')->nullable();
            $t->unsignedBigInteger('base_npc_id')->nullable();
            $t->boolean('auto_start_dialog')->default(false);
            $t->boolean('enabled')->default(true);
        });
        $timestamped('npc_locations', function (Blueprint $t): void {
            $t->unsignedBigInteger('npc_id');
            $t->unsignedBigInteger('map_id');
            $t->integer('x');
            $t->integer('y');
        });
        $timestamped('maps', function (Blueprint $t): void {
            $t->string('name');
            $t->boolean('is_internal')->default(false);
        });
        $timestamped('base_npcs', function (Blueprint $t): void {
            $t->string('name');
            $t->integer('lvl')->nullable();
            $t->string('src')->nullable();
            $t->string('rank')->nullable();
        });
        $timestamped('mob_species', fn (Blueprint $t) => $t->string('name'));
        $timestamped('base_items', function (Blueprint $t): void {
            $t->string('name');
            $t->string('src')->nullable();
        });
    }
}
