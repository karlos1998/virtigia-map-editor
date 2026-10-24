<?php

namespace Tests\Feature;

use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\Npc;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\NpcFilterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NpcBulkManagementTest extends TestCase
{
    use RefreshDatabase;

    private BaseNpc $base;

    private Dialog $dialog;

    private Dialog $replacement;

    private Npc $withDialog;

    private Npc $withoutDialog;

    private Npc $unselected;

    private Map $map;

    protected function setUp(): void
    {
        parent::setUp();

        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => config('database.default')]);
        DynamicModel::setGlobalConnection(config('database.default'));
        $this->createWorldTables();
        $this->actingAs(User::query()->create([
            'login' => 'npc-editor', 'name' => 'NPC Editor', 'email' => 'npc-editor@example.com',
            'roles' => ['admin'], 'permissions' => ['world.write'], 'src' => 'retro/avatar.png',
        ]))->withSession(['world' => 'retro']);
        $this->base = BaseNpc::factory()->create([
            'name' => 'Wampir', 'src' => 'retro/vampire.gif', 'lvl' => 50,
            'rank' => 'NORMAL', 'category' => 'NPC', 'profession' => 'w',
        ]);
        $this->dialog = Dialog::query()->create(['name' => 'Rozmowa z wampirem']);
        $this->replacement = Dialog::query()->create(['name' => 'Nowa rozmowa']);
        $this->withDialog = $this->makeNpc($this->dialog, true);
        $this->withoutDialog = $this->makeNpc();
        $this->unselected = $this->makeNpc($this->dialog, true);
        $this->map = Map::query()->create(['name' => 'Krypta', 'src' => 'crypt.png', 'x' => 20, 'y' => 20]);
        $this->withDialog->locations()->create(['map_id' => $this->map->id, 'x' => 1, 'y' => 1]);
        $this->withoutDialog->locations()->create(['map_id' => $this->map->id, 'x' => 2, 'y' => 2]);
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();
        parent::tearDown();
    }

    public function test_assigning_only_empty_dialogs_preserves_existing_and_unselected_instances(): void
    {
        $this->bulk('assign_dialog', ['dialog_id' => $this->replacement->id, 'assignment_mode' => 'empty'])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success', 'Zaktualizowano 1 instancji NPC.');
        $this->assertSame($this->dialog->id, $this->withDialog->fresh()->dialog_id);
        $this->assertSame($this->replacement->id, $this->withoutDialog->fresh()->dialog_id);
        $this->assertSame($this->dialog->id, $this->unselected->fresh()->dialog_id);
    }

    public function test_replacing_dialogs_updates_selected_instances_and_records_history(): void
    {
        $this->bulk('assign_dialog', ['dialog_id' => $this->replacement->id, 'assignment_mode' => 'replace'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($this->replacement->id, $this->withDialog->fresh()->dialog_id);
        $this->assertSame($this->replacement->id, $this->withoutDialog->fresh()->dialog_id);
        $this->assertSame($this->dialog->id, $this->unselected->fresh()->dialog_id);
        $this->assertDatabaseHas('activity_log', ['subject_type' => Npc::class, 'subject_id' => $this->withDialog->id, 'event' => 'updated']);
    }

    public function test_detaching_dialogs_disables_auto_start_without_deleting_shared_dialog(): void
    {
        $this->bulk('detach_dialog')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($this->withDialog->fresh()->dialog_id);
        $this->assertFalse($this->withDialog->fresh()->auto_start_dialog);
        $this->assertSame($this->dialog->id, $this->unselected->fresh()->dialog_id);
        $this->assertModelExists($this->dialog);
    }

    #[DataProvider('statusOperations')]
    public function test_status_operations_change_only_selected_instances(string $operation, bool $enabled): void
    {
        $this->bulk($operation)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($enabled, $this->withDialog->fresh()->enabled);
        $this->assertSame($enabled, $this->withoutDialog->fresh()->enabled);
        $this->assertTrue($this->unselected->fresh()->enabled);
    }

    public static function statusOperations(): array
    {
        return [['enable', true], ['disable', false]];
    }

    #[DataProvider('aggressionOperations')]
    public function test_aggression_updates_unique_base_mobs_and_all_their_instances(string $operation, bool $aggressive): void
    {
        $this->base->update(['category' => 'MOB', 'is_aggressive' => ! $aggressive]);
        $otherBase = BaseNpc::factory()->create([
            'name' => 'Inny mob', 'src' => '', 'category' => 'MOB', 'rank' => 'NORMAL', 'profession' => 'w', 'is_aggressive' => ! $aggressive,
        ]);
        $this->makeNpc(base: $otherBase);
        $this->bulk($operation)->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Zmieniono agresywność 1 bazowych mobów (wszystkie ich instancje).');
        $this->assertSame($aggressive, $this->base->fresh()->is_aggressive);
        $this->assertSame($aggressive, $this->unselected->fresh()->base->is_aggressive);
        $this->assertSame(! $aggressive, $otherBase->fresh()->is_aggressive);
        $this->assertDatabaseHas('activity_log', ['subject_type' => BaseNpc::class, 'subject_id' => $this->base->id, 'event' => 'updated']);
    }

    public static function aggressionOperations(): array
    {
        return [['enable_aggression', true], ['disable_aggression', false]];
    }

    public function test_aggression_rejects_mixed_npc_and_mob_selection_without_partial_changes(): void
    {
        $mobBase = BaseNpc::factory()->create([
            'name' => 'Test mob', 'src' => '', 'category' => 'MOB', 'rank' => 'NORMAL', 'profession' => 'w', 'is_aggressive' => false,
        ]);
        $mob = $this->makeNpc(base: $mobBase);
        $this->bulk('enable_aggression', ['npc_ids' => [$this->withDialog->id, $mob->id]])->assertSessionHasErrors('npc_ids');
        $this->assertFalse($mobBase->fresh()->is_aggressive);
        $this->assertFalse($this->base->fresh()->is_aggressive);
    }

    public function test_auto_dialog_requires_dialogs_on_every_selected_instance_and_is_atomic(): void
    {
        $this->withDialog->update(['auto_start_dialog' => false]);
        $this->bulk('enable_auto_dialog')->assertSessionHasErrors('npc_ids');
        $this->assertFalse($this->withDialog->fresh()->auto_start_dialog);
        $this->bulk('enable_auto_dialog', ['npc_ids' => [$this->withDialog->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($this->withDialog->fresh()->auto_start_dialog);
        $this->bulk('disable_auto_dialog')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($this->withDialog->fresh()->auto_start_dialog);
    }

    #[DataProvider('invalidBulkRequests')]
    public function test_bulk_validation_rejects_invalid_requests(array $payload, string $error): void
    {
        $this->bulk('detach_dialog', $payload)->assertSessionHasErrors($error);
        $this->assertSame($this->dialog->id, $this->withDialog->fresh()->dialog_id);
    }

    public static function invalidBulkRequests(): array
    {
        return [
            'empty selection' => [['npc_ids' => []], 'npc_ids'],
            'unknown npc' => [['npc_ids' => [999999]], 'npc_ids.0'],
            'duplicate ids' => [['npc_ids' => [1, 1]], 'npc_ids.0'],
            'too many' => [['npc_ids' => range(1, 501)], 'npc_ids'],
            'unknown operation' => [['operation' => 'delete'], 'operation'],
            'missing dialog' => [['operation' => 'assign_dialog', 'assignment_mode' => 'replace'], 'dialog_id'],
            'invalid dialog' => [['operation' => 'assign_dialog', 'dialog_id' => 999999, 'assignment_mode' => 'replace'], 'dialog_id'],
            'missing assignment mode' => [['operation' => 'assign_dialog', 'dialog_id' => 1], 'assignment_mode'],
        ];
    }

    public function test_conversion_requires_explicit_choice_and_preserves_data_on_error(): void
    {
        $this->convert()->assertSessionHasErrors('instance_dialog_action');
        $this->assertSame('NPC', $this->base->fresh()->category->value);
        $this->assertSame($this->dialog->id, $this->withDialog->fresh()->dialog_id);
    }

    public function test_conversion_can_keep_dialogs(): void
    {
        $this->convert(['instance_dialog_action' => 'keep'])->assertOk()->assertSessionHasNoErrors();
        $this->assertSame('MOB', $this->base->fresh()->category->value);
        $this->assertSame($this->dialog->id, $this->withDialog->fresh()->dialog_id);
        $this->assertTrue($this->withDialog->fresh()->auto_start_dialog);
    }

    public function test_conversion_detaches_dialogs_on_every_instance_including_other_maps(): void
    {
        $otherBase = BaseNpc::factory()->create(['name' => 'Inny', 'src' => '', 'category' => 'NPC', 'rank' => 'NORMAL', 'profession' => 'w']);
        $other = $this->makeNpc($this->dialog, true, $otherBase);
        $this->convert(['instance_dialog_action' => 'detach'])->assertOk()->assertSessionHasNoErrors();
        foreach ([$this->withDialog, $this->withoutDialog, $this->unselected] as $npc) {
            $this->assertNull($npc->fresh()->dialog_id);
            $this->assertFalse($npc->fresh()->auto_start_dialog);
        }
        $this->assertSame($this->dialog->id, $other->fresh()->dialog_id);
        $this->assertModelExists($this->dialog);
    }

    public function test_unrelated_mob_edit_cannot_detach_dialogs(): void
    {
        $this->base->update(['category' => 'MOB']);
        $this->convert(['instance_dialog_action' => 'detach'])->assertOk();
        $this->assertSame($this->dialog->id, $this->withDialog->fresh()->dialog_id);
    }

    public function test_conversion_without_dialogs_does_not_require_choice(): void
    {
        $this->withDialog->dialog()->dissociate()->save();
        $this->unselected->dialog()->dissociate()->save();
        $this->convert()->assertOk()->assertSessionHasNoErrors();
        $this->assertSame('MOB', $this->base->fresh()->category->value);
    }

    public function test_combined_filters_and_selected_labels_are_returned_in_npc_index(): void
    {
        $this->get(route('npcs.index', ['map_id' => $this->map->id, 'has_dialog' => 1, 'category' => 'NPC', 'min_lvl' => 50, 'max_lvl' => 50, 'dialog_id' => $this->dialog->id]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Npc/Index')
            ->has('npcs.data', 1)->where('npcs.data.0.id', $this->withDialog->id)
            ->where('selectedMap.name', 'Krypta')->where('selectedDialog.name', 'Rozmowa z wampirem'));
    }

    public function test_base_filters_require_map_and_dialog_on_the_same_instance(): void
    {
        $this->withDialog->locations()->delete();
        $filters = app(NpcFilterService::class);
        $this->assertSame([], $filters->apply(BaseNpc::query(), ['map_id' => $this->map->id, 'has_dialog' => 1], true)->pluck('id')->all());
        $this->assertSame([$this->base->id], $filters->apply(BaseNpc::query(), ['map_id' => $this->map->id, 'has_dialog' => 0], true)->pluck('id')->all());
    }

    public function test_base_index_supports_combined_filters_and_restores_numeric_filter_values(): void
    {
        $this->get(route('base-npcs.index', ['map_id' => $this->map->id, 'has_dialog' => '1', 'enabled' => '1', 'min_lvl' => '0']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('BaseNpc/Index')
            ->has('baseNpcs.data', 1)->where('baseNpcs.data.0.id', $this->base->id)
            ->where('filters.has_dialog', 1)->where('filters.min_lvl', 0)->where('selectedMap.name', 'Krypta'));
    }

    public function test_conversion_warning_counts_instances_rather_than_spawn_coordinates(): void
    {
        $this->withDialog->locations()->create(['map_id' => $this->map->id, 'x' => 3, 'y' => 3]);
        $this->base->loadCount(['locations as dialog_instances_count' => fn ($query) => $query->whereNotNull('dialog_id')]);
        $this->base->setRelation('loots', collect());
        $data = \App\Http\Resources\BaseNpcResource::make($this->base)->resolve();
        $this->assertSame(2, $data['dialog_instances_count']);
    }

    public function test_bulk_validation_uses_the_selected_world_for_npcs_and_dialogs(): void
    {
        $connection = config('database.default');
        config()->set('database.connections.npc_test_other_world', [
            ...config("database.connections.{$connection}"), 'prefix' => 'other_world_',
        ]);
        DB::connection('npc_test_other_world')->setPdo(DB::connection()->getPdo());
        Schema::connection('npc_test_other_world')->create('npcs', fn (Blueprint $table) => $table->id());
        Schema::connection('npc_test_other_world')->create('dialogs', fn (Blueprint $table) => $table->id());
        WorldTemplate::query()->create([
            'name' => 'Other test world', 'slug' => 'npc-test-other', 'connection_name' => 'npc_test_other_world',
            'remote_database_server' => 'test', 'database_name' => 'test', 'is_active' => true, 'is_visible' => true, 'is_production' => false,
        ]);
        $this->withSession(['world' => 'npc-test-other']);
        $this->bulk('assign_dialog', ['dialog_id' => $this->dialog->id, 'assignment_mode' => 'replace'])
            ->assertSessionHasErrors(['npc_ids.0', 'dialog_id']);
        DynamicModel::setGlobalConnection($connection);
        $this->assertSame($this->dialog->id, $this->withDialog->fresh()->dialog_id);
        DB::purge('npc_test_other_world');
    }

    public function test_filters_handle_false_zero_group_and_multiple_locations(): void
    {
        $this->withoutDialog->update(['enabled' => false, 'group_id' => 1]);
        $this->withDialog->locations()->create(['map_id' => $this->map->id, 'x' => 3, 'y' => 3]);
        $filters = app(NpcFilterService::class);
        $this->assertSame([$this->withoutDialog->id], $filters->apply(Npc::query(), ['enabled' => 0, 'has_dialog' => 0, 'in_group' => 1, 'auto_start_dialog' => 0])->pluck('id')->all());
        $this->assertSame([$this->withDialog->id], $filters->apply(Npc::query(), ['placement' => 'many', 'base_npc_id' => $this->base->id, 'rank' => 'NORMAL', 'is_aggressive' => 0])->pluck('id')->all());
        $this->assertSame([$this->unselected->id], $filters->apply(Npc::query(), ['placement' => 'none'])->pluck('id')->all());
        $this->base->update(['lvl' => 0]);
        $this->assertCount(3, $filters->apply(Npc::query(), ['min_lvl' => 0, 'max_lvl' => 0])->get());
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->get(route('npcs.index', ['min_lvl' => 30, 'max_lvl' => 20]))->assertSessionHasErrors('max_lvl');
        $this->get(route('base-npcs.index', ['map_id' => 999999]))->assertSessionHasErrors('map_id');
    }

    public function test_unauthenticated_and_locked_users_cannot_bulk_update(): void
    {
        auth()->logout();
        $this->bulk('detach_dialog')->assertRedirect(route('login'));
        $this->actingAs(User::query()->create(['login' => 'locked', 'name' => 'Locked', 'email' => 'locked@example.com', 'roles' => [], 'permissions' => [], 'src' => 'retro/avatar.png']));
        $this->bulk('detach_dialog')->assertRedirect(route('locked'));
        $this->assertSame($this->dialog->id, $this->withDialog->fresh()->dialog_id);
    }

    private function bulk(string $operation, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->patch(route('npcs.bulk.update'), array_replace(['npc_ids' => [$this->withDialog->id, $this->withoutDialog->id], 'operation' => $operation], $data));
    }

    private function convert(array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->patch(route('base-npcs.update', $this->base), array_replace([
            'name' => 'Wampir', 'lvl' => 50, 'rank' => 'NORMAL', 'category' => 'MOB', 'profession' => 'w',
        ], $data));
    }

    private function makeNpc(?Dialog $dialog = null, bool $auto = false, ?BaseNpc $base = null): Npc
    {
        $npc = new Npc;
        $npc->base()->associate($base ?? $this->base);
        $npc->dialog()->associate($dialog);
        $npc->auto_start_dialog = $auto;
        $npc->save();

        return $npc;
    }

    private function createWorldTables(): void
    {
        Schema::create('base_npcs', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('src');
            $table->integer('lvl')->default(0);
            $table->string('rank');
            $table->string('category');
            $table->string('profession');
            $table->boolean('is_aggressive')->default(false);
            $table->integer('draw_offset_x')->default(0);
            $table->integer('draw_offset_y')->default(0);
            $table->string('layer_depth')->default('Y_SORT');
        });
        Schema::create('dialogs', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name');
        });
        Schema::create('npcs', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('base_npc_id');
            $table->unsignedBigInteger('dialog_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('auto_start_dialog')->default(false);
            $table->integer('auto_start_dialog_range')->default(1);
            $table->boolean('manually_group_detached')->default(false);
        });
        Schema::create('maps', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_city')->default(false);
            $table->timestamps();
            $table->string('name');
            $table->string('src');
            $table->integer('x');
            $table->integer('y');
        });
        Schema::create('npc_locations', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('npc_id');
            $table->unsignedBigInteger('map_id');
            $table->integer('x');
            $table->integer('y');
        });
        Schema::create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('base_npc_loots', function (Blueprint $table): void {
            $table->unsignedBigInteger('base_npc_id');
            $table->unsignedBigInteger('base_item_id');
        });
    }
}
