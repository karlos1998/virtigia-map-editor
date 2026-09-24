<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\Virtigia\AnalyzeRetroLootTool;
use App\Mcp\Tools\Virtigia\ApplyChangeSetTool;
use App\Mcp\Tools\Virtigia\DraftChangeSetTool;
use App\Mcp\Tools\Virtigia\GetBaseItemTool;
use App\Mcp\Tools\Virtigia\GetDialogGraphTool;
use App\Mcp\Tools\Virtigia\GetQuestTool;
use App\Mcp\Tools\Virtigia\GetRetroBuildOptionsTool;
use App\Mcp\Tools\Virtigia\GetShopInventoryTool;
use App\Mcp\Tools\Virtigia\GetWritingContextTool;
use App\Mcp\Tools\Virtigia\InspectRetroNpcTool;
use App\Mcp\Tools\Virtigia\ListChangeSetsTool;
use App\Mcp\Tools\Virtigia\ProfileTool;
use App\Mcp\Tools\Virtigia\RevertChangeSetTool;
use App\Mcp\Tools\Virtigia\SearchGameContentTool;
use App\Mcp\Tools\Virtigia\SimulateRetroCombatTool;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\BaseNpcLoot;
use App\Models\Dialog;
use App\Models\DynamicModel;
use App\Models\Quest;
use App\Models\QuestStepAutoProgress;
use App\Models\QuestStepAutoProgressMob;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Services\Mcp\AiChangeSetService;
use App\Services\Mcp\GameContentSearchService;
use App\Services\Mcp\McpWorldService;
use App\Services\Mcp\RetroEngineAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VirtigiaContentServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_resource_metadata_points_to_virtigia_oauth(): void
    {
        config()->set('services.virtigia_page.url', 'https://virtigia.example');

        $this->getJson('/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJson([
                'resource' => url('/mcp/virtigia'),
                'authorization_servers' => ['https://virtigia.example'],
                'scopes_supported' => ['mcp:use'],
            ]);
    }

    public function test_mcp_endpoint_requires_a_virtigia_access_token(): void
    {
        $this->postJson('/mcp/virtigia', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
        ])->assertUnauthorized()
            ->assertHeader('WWW-Authenticate');
    }

    public function test_employee_can_initialize_an_authenticated_mcp_session(): void
    {
        config()->set('services.virtigia_page.url', 'https://virtigia.example');
        Http::fake([
            'https://virtigia.example/api/mcp/profile' => Http::response([
                'id' => 321,
                'login' => 'quest-maker',
                'name' => 'Quest Maker',
                'email' => 'quest-maker@example.com',
                'map_editor_access' => true,
                'forum_background_src' => null,
                'src' => '',
                'roles' => ['game_master'],
                'permissions' => ['map-editor-access'],
            ]),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-virtigia-token',
            'Accept' => 'application/json, text/event-stream',
        ])->postJson('/mcp/virtigia', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-03-26',
                'capabilities' => (object) [],
                'clientInfo' => ['name' => 'Codex test', 'version' => '1.0.0'],
            ],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('Virtigia Content Server', $response->getContent());
        $this->assertDatabaseHas('users', ['id' => 321, 'login' => 'quest-maker']);
    }

    public function test_forbidden_base_npc_creation_is_rejected(): void
    {
        $validation = app(AiChangeSetService::class)->validateOperations([
            ['type' => 'create_base_npc', 'data' => ['name' => 'NPC AI']],
        ]);

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    public function test_new_base_item_with_valid_image_can_be_drafted(): void
    {
        $validation = app(AiChangeSetService::class)->validateOperations([[
            'type' => 'create_base_item',
            'key' => 'quest_token',
            'data' => [
                'name' => 'Żeton zadania',
                'category' => 'quests',
                'rarity' => 'common',
                'price' => 0,
                'currency' => 'unset',
                'attributes' => ['description' => 'Przedmiot testowy'],
                'image_data_uri' => $this->itemImageDataUri(),
            ],
        ]]);

        $this->assertTrue($validation['valid'], implode("\n", $validation['errors']));
    }

    public function test_new_base_item_rejects_image_with_wrong_dimensions(): void
    {
        $validation = app(AiChangeSetService::class)->validateOperations([[
            'type' => 'create_base_item',
            'key' => 'bad_icon',
            'data' => [
                'name' => 'Błędna ikona',
                'category' => 'quests',
                'rarity' => 'common',
                'price' => 0,
                'currency' => 'unset',
                'image_data_uri' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=',
            ],
        ]]);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('32×32', implode(' ', $validation['errors']));
    }

    public function test_base_item_change_rejects_unsupported_fields_instead_of_ignoring_them(): void
    {
        $validation = app(AiChangeSetService::class)->validateOperations([[
            'type' => 'create_base_item',
            'key' => 'bad_item',
            'data' => [
                'name' => 'Niepoprawny item',
                'category' => 'quests',
                'rarity' => 'common',
                'price' => 0,
                'currency' => 'unset',
                'src' => 'unsafe/manual-path.png',
                'image_data_uri' => $this->itemImageDataUri(),
            ],
        ]]);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('nieobsługiwane pola BaseItemu: src', implode(' ', $validation['errors']));
    }

    public function test_presented_change_set_redacts_embedded_item_image(): void
    {
        $changeSet = new \App\Models\AiChangeSet([
            'operations' => [[
                'type' => 'create_base_item',
                'key' => 'quest_token',
                'data' => [
                    'name' => 'Żeton zadania',
                    'image_data_uri' => $this->itemImageDataUri(),
                ],
            ]],
        ]);

        $presented = app(AiChangeSetService::class)->present($changeSet);

        $this->assertStringStartsWith('[image data omitted;', $presented['operations'][0]['data']['image_data_uri']);
        $this->assertStringNotContainsString('base64,', $presented['operations'][0]['data']['image_data_uri']);
    }

    public function test_quest_rejects_invented_kill_fields_instead_of_ignoring_them(): void
    {
        $validation = app(AiChangeSetService::class)->validateOperations([[
            'type' => 'create_quest',
            'key' => 'broken_kill_quest',
            'data' => [
                'name' => 'Błędny quest zabójstw',
                'steps' => [[
                    'key' => 'kill_mobs',
                    'name' => 'Zabij przeciwników',
                    'description' => 'Zabij dziesięciu przeciwników.',
                    'kill_targets' => [['base_npc_id' => 1189, 'quantity' => 10]],
                    'on_complete_step_key' => 'return_to_npc',
                ], [
                    'key' => 'return_to_npc',
                    'name' => 'Wróć do zleceniodawcy',
                    'description' => 'Odbierz nagrodę.',
                ]],
            ],
        ]]);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('kill_targets', implode(' ', $validation['errors']));
        $this->assertStringContainsString('on_complete_step_key', implode(' ', $validation['errors']));
    }

    public function test_quest_commit_persists_reads_patches_and_reverts_real_mob_progress(): void
    {
        app(McpWorldService::class)->use('test');

        try {
            $user = \App\Models\User::factory()->create();
            $monk = BaseNpc::query()->create([
                'name' => 'Mnich testowy MCP',
                'src' => 'test/monk.gif',
                'lvl' => 20,
                'category' => 'MOB',
                'profession' => 'w',
            ]);
            $leader = BaseNpc::query()->create([
                'name' => 'Przywódca testowy MCP',
                'src' => 'test/leader.gif',
                'lvl' => 25,
                'category' => 'MOB',
                'profession' => 'w',
            ]);
            $service = app(AiChangeSetService::class);
            $createChangeSet = $service->draft($user, 'test', 'Quest zabójstw MCP', null, [[
                'type' => 'create_quest',
                'key' => 'monk_hunt',
                'data' => [
                    'name' => 'Polowanie testowe MCP',
                    'steps' => [[
                        'key' => 'accept',
                        'name' => 'Przyjmij zadanie',
                        'description' => 'Porozmawiaj ze zleceniodawcą.',
                        'auto_advance_next_day' => true,
                        'auto_advance_to_step_key' => 'kill',
                    ], [
                        'key' => 'kill',
                        'name' => 'Pokonaj przeciwników',
                        'description' => 'Pokonaj mnichów i ich przywódcę.',
                        'auto_progress' => [
                            'type' => 'mobs',
                            'mobs' => [
                                ['type' => 'base_npc', 'base_npc_id' => $monk->id, 'quantity' => 10],
                                ['type' => 'base_npc', 'base_npc_id' => $leader->id, 'quantity' => 1],
                            ],
                        ],
                    ], [
                        'key' => 'return',
                        'name' => 'Wróć do zleceniodawcy',
                        'description' => 'Odbierz nagrodę.',
                    ]],
                ],
            ]]);

            $created = $service->apply($user, $createChangeSet->id, 1);
            $questId = $created->result['references']['quests']['monk_hunt'];
            $acceptStepId = $created->result['references']['steps']['monk_hunt:accept'];
            $killStepId = $created->result['references']['steps']['monk_hunt:kill'];
            $autoProgress = QuestStepAutoProgress::query()->where('quest_step_id', $killStepId)->firstOrFail();

            $this->assertSame('mobs', $autoProgress->type);
            $this->assertEqualsCanonicalizing([
                ['base_npc_id' => $monk->id, 'quantity' => 10],
                ['base_npc_id' => $leader->id, 'quantity' => 1],
            ], QuestStepAutoProgressMob::query()
                ->where('quest_step_auto_progress_id', $autoProgress->id)
                ->get(['base_npc_id', 'quantity'])
                ->map(fn (QuestStepAutoProgressMob $target): array => [
                    'base_npc_id' => $target->base_npc_id,
                    'quantity' => $target->quantity,
                ])->all());

            $readBack = app(GameContentSearchService::class)->quest('test', $questId);
            $killStep = collect($readBack['quest']['steps'])->firstWhere('id', $killStepId);
            $acceptStep = collect($readBack['quest']['steps'])->firstWhere('id', $acceptStepId);
            $this->assertSame('mobs', $killStep['auto_progress']['type']);
            $this->assertSame(10, $killStep['auto_progress']['mobs'][0]['quantity']);
            $this->assertTrue($acceptStep['auto_advance_next_day']);
            $this->assertSame($killStepId, $acceptStep['auto_advance_to_step_id']);
            $this->assertTrue($readBack['engine_behavior']['description_is_not_logic']);

            $patchChangeSet = $service->draft($user, 'test', 'Popraw ilość mnichów MCP', null, [[
                'type' => 'patch_quest',
                'quest_id' => $questId,
                'data' => [
                    'steps' => [[
                        'step_id' => $killStepId,
                        'auto_progress' => [
                            'type' => 'mobs',
                            'mobs' => [
                                ['type' => 'base_npc', 'base_npc_id' => $monk->id, 'quantity' => 15],
                                ['type' => 'base_npc', 'base_npc_id' => $leader->id, 'quantity' => 1],
                            ],
                        ],
                    ]],
                ],
            ]]);
            $service->apply($user, $patchChangeSet->id, 1);

            $patched = app(GameContentSearchService::class)->quest('test', $questId);
            $patchedKillStep = collect($patched['quest']['steps'])->firstWhere('id', $killStepId);
            $this->assertSame(15, $patchedKillStep['auto_progress']['mobs'][0]['quantity']);

            $service->revert($user, $patchChangeSet->id);
            $reverted = app(GameContentSearchService::class)->quest('test', $questId);
            $revertedKillStep = collect($reverted['quest']['steps'])->firstWhere('id', $killStepId);
            $this->assertSame(10, $revertedKillStep['auto_progress']['mobs'][0]['quantity']);

            $service->revert($user, $createChangeSet->id);
            $this->assertNull(Quest::query()->find($questId));
        } finally {
            app(McpWorldService::class)->use('test');
            Quest::query()->where('name', 'Polowanie testowe MCP')->delete();
            BaseNpc::query()->whereIn('name', ['Mnich testowy MCP', 'Przywódca testowy MCP'])->delete();
            DynamicModel::clearGlobalConnection();
        }
    }

    public function test_item_commit_can_create_assign_and_revert_everything(): void
    {
        Storage::fake('s3');
        app(McpWorldService::class)->use('test');

        try {
            $user = \App\Models\User::factory()->create();
            $baseNpc = BaseNpc::query()->create([
                'name' => 'NPC testowy MCP',
                'src' => 'test/mcp.gif',
                'lvl' => 1,
                'category' => 'NPC',
                'profession' => 'w',
            ]);
            $shop = Shop::query()->create(['name' => 'Sklep testowy MCP']);
            $sourceItem = BaseItem::query()->create([
                'name' => 'Miecz źródłowy MCP',
                'src' => 'items/test/source.png',
                'stats' => '',
                'cl' => 0,
                'pr' => 0,
                'edited_manually' => true,
                'attributes' => ['physicalDamage' => 100, 'obsoleteBonus' => 1],
                'rarity' => 'common',
                'category' => 'oneHanded',
                'price' => 100,
                'currency' => 'gold',
            ]);
            $service = app(AiChangeSetService::class);
            $changeSet = $service->draft($user, 'test', 'Nowy przedmiot testowy', null, [
                [
                    'type' => 'clone_base_item',
                    'key' => 'improved_sword',
                    'source_base_item_id' => $sourceItem->id,
                    'data' => [
                        'name' => 'Lepszy miecz MCP',
                        'rarity' => 'unique',
                        'attributes_patch' => ['physicalDamage' => 120],
                        'remove_attributes' => ['obsoleteBonus'],
                    ],
                ],
                [
                    'type' => 'update_base_item',
                    'item_id' => $sourceItem->id,
                    'data' => [
                        'attributes_patch' => ['physicalDamage' => 110],
                    ],
                ],
                [
                    'type' => 'create_base_item',
                    'key' => 'quest_token',
                    'data' => [
                        'name' => 'Żeton zadania MCP',
                        'category' => 'quests',
                        'rarity' => 'common',
                        'price' => 0,
                        'currency' => 'unset',
                        'attributes' => ['description' => 'Nagroda z testowego zadania'],
                        'image_data_uri' => $this->itemImageDataUri(),
                    ],
                ],
                [
                    'type' => 'attach_item_to_shop',
                    'shop_id' => $shop->id,
                    'item_key' => 'quest_token',
                    'row' => 9,
                    'column' => 7,
                ],
                [
                    'type' => 'attach_item_to_base_npc_loot',
                    'base_npc_id' => $baseNpc->id,
                    'item_key' => 'quest_token',
                ],
                [
                    'type' => 'create_quest',
                    'key' => 'item_quest',
                    'data' => [
                        'name' => 'Test itemu MCP',
                        'steps' => [[
                            'key' => 'start',
                            'name' => 'Odbierz przedmiot',
                            'description' => 'Odbierz nowy przedmiot.',
                        ]],
                    ],
                ],
                [
                    'type' => 'create_dialog',
                    'key' => 'item_dialog',
                    'data' => [
                        'name' => 'Nagroda itemowa MCP',
                        'nodes' => [[
                            'key' => 'reward',
                            'content' => 'Oto twoja nagroda.',
                            'additional_actions' => [
                                'addItems' => ['value' => ['@item:quest_token']],
                            ],
                            'options' => [[
                                'key' => 'finish',
                                'label' => 'Dziękuję.',
                            ]],
                        ]],
                    ],
                ],
            ]);

            $applied = $service->apply($user, $changeSet->id, 1);
            $itemId = $applied->result['references']['items']['quest_token'];
            $clonedItemId = $applied->result['references']['items']['improved_sword'];
            $dialogId = $applied->result['references']['dialogs']['item_dialog'];
            $questId = $applied->result['references']['quests']['item_quest'];

            $item = BaseItem::query()->findOrFail($itemId);
            $clonedItem = BaseItem::query()->findOrFail($clonedItemId);
            Storage::disk('s3')->assertExists('img/'.$item->src);
            $this->assertSame('items/test/source.png', $clonedItem->src);
            $this->assertSame(120, $clonedItem->attributes['physicalDamage']);
            $this->assertArrayNotHasKey('obsoleteBonus', $clonedItem->attributes);
            $this->assertSame(110, BaseItem::query()->findOrFail($sourceItem->id)->attributes['physicalDamage']);
            $this->assertTrue(ShopItem::query()->where(['shop_id' => $shop->id, 'item_id' => $itemId, 'position' => 79])->exists());
            $this->assertTrue(BaseNpcLoot::query()->where(['base_npc_id' => $baseNpc->id, 'base_item_id' => $itemId])->exists());
            $this->assertSame($itemId, Dialog::query()->findOrFail($dialogId)->nodes()->firstOrFail()->additional_actions['addItems']['value'][0]);

            $itemDetails = app(GameContentSearchService::class)->baseItem('test', $itemId);
            $shopInventory = app(GameContentSearchService::class)->shopInventory('test', $shop->id);
            $this->assertSame('Żeton zadania MCP', $itemDetails['item']['name']);
            $this->assertSame(79, $shopInventory['shop']['items'][0]['position']);
            $this->assertNotContains(79, $shopInventory['shop']['free_positions']);

            $reverted = $service->revert($user, $changeSet->id);

            $this->assertSame('reverted', $reverted->status);
            $this->assertNull(BaseItem::withTrashed()->find($itemId));
            $this->assertNull(BaseItem::withTrashed()->find($clonedItemId));
            $this->assertSame(['physicalDamage' => 100, 'obsoleteBonus' => 1], BaseItem::query()->findOrFail($sourceItem->id)->attributes);
            $this->assertNull(Dialog::query()->find($dialogId));
            $this->assertNull(Quest::query()->find($questId));
            $this->assertFalse(ShopItem::query()->where('item_id', $itemId)->exists());
            $this->assertFalse(BaseNpcLoot::query()->where('base_item_id', $itemId)->exists());
        } finally {
            app(McpWorldService::class)->use('test');
            ShopItem::query()->whereIn('shop_id', Shop::query()->where('name', 'Sklep testowy MCP')->pluck('id'))->delete();
            BaseNpcLoot::query()->whereIn('base_npc_id', BaseNpc::query()->where('name', 'NPC testowy MCP')->pluck('id'))->delete();
            Dialog::query()->where('name', 'Nagroda itemowa MCP')->delete();
            Quest::query()->where('name', 'Test itemu MCP')->delete();
            BaseItem::withTrashed()->where('name', 'Żeton zadania MCP')->forceDelete();
            BaseItem::withTrashed()->whereIn('name', ['Miecz źródłowy MCP', 'Lepszy miecz MCP'])->forceDelete();
            Shop::query()->where('name', 'Sklep testowy MCP')->delete();
            BaseNpc::query()->where('name', 'NPC testowy MCP')->delete();
            DynamicModel::clearGlobalConnection();
        }
    }

    public function test_retro_engine_proxy_fails_closed_without_server_side_token(): void
    {
        config()->set('services.virtigia_retro_engine.token', null);
        Http::preventStrayRequests();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('nie ma skonfigurowanych poświadczeń');

        app(RetroEngineAnalysisService::class)->npc(1);
    }

    public function test_retro_build_tool_exposes_availability_filters(): void
    {
        $schema = app(GetRetroBuildOptionsTool::class)->toArray()['inputSchema']['properties'];

        $this->assertArrayHasKey('max_rarity', $schema);
        $this->assertArrayHasKey('obtainable_only', $schema);
        $this->assertArrayHasKey('allowed_sources', $schema);
        $this->assertArrayHasKey('exclude_event_sources', $schema);
        $this->assertArrayHasKey('exclude_admin_shops', $schema);
    }

    /** @param class-string $toolClass */
    #[DataProvider('toolNames')]
    public function test_tools_expose_stable_names(string $toolClass, string $expectedName): void
    {
        $tool = app($toolClass);

        $this->assertSame($expectedName, $tool->name());
        $this->assertArrayHasKey('inputSchema', $tool->toArray());
    }

    /** @return array<string, array{class-string, string}> */
    public static function toolNames(): array
    {
        return [
            'profile' => [ProfileTool::class, 'profile'],
            'search' => [SearchGameContentTool::class, 'search_game_content'],
            'base item' => [GetBaseItemTool::class, 'get_base_item'],
            'shop inventory' => [GetShopInventoryTool::class, 'get_shop_inventory'],
            'writing context' => [GetWritingContextTool::class, 'get_writing_context'],
            'dialog graph' => [GetDialogGraphTool::class, 'get_dialog_graph'],
            'quest' => [GetQuestTool::class, 'get_quest'],
            'inspect retro npc' => [InspectRetroNpcTool::class, 'inspect_retro_npc'],
            'retro build options' => [GetRetroBuildOptionsTool::class, 'get_retro_build_options'],
            'retro combat simulation' => [SimulateRetroCombatTool::class, 'simulate_retro_combat'],
            'retro loot analysis' => [AnalyzeRetroLootTool::class, 'analyze_retro_loot'],
            'draft' => [DraftChangeSetTool::class, 'draft_change_set'],
            'apply' => [ApplyChangeSetTool::class, 'apply_change_set'],
            'list' => [ListChangeSetsTool::class, 'list_change_sets'],
            'revert' => [RevertChangeSetTool::class, 'revert_change_set'],
        ];
    }

    private function itemImageDataUri(): string
    {
        $image = imagecreatetruecolor(32, 32);
        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($contents);
    }
}
