<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\Virtigia\AnalyzeRetroLootTool;
use App\Mcp\Tools\Virtigia\ApplyChangeSetTool;
use App\Mcp\Tools\Virtigia\DraftChangeSetTool;
use App\Mcp\Tools\Virtigia\GetDialogGraphTool;
use App\Mcp\Tools\Virtigia\GetRetroBuildOptionsTool;
use App\Mcp\Tools\Virtigia\GetWritingContextTool;
use App\Mcp\Tools\Virtigia\InspectRetroNpcTool;
use App\Mcp\Tools\Virtigia\ListChangeSetsTool;
use App\Mcp\Tools\Virtigia\ProfileTool;
use App\Mcp\Tools\Virtigia\RevertChangeSetTool;
use App\Mcp\Tools\Virtigia\SearchGameContentTool;
use App\Mcp\Tools\Virtigia\SimulateRetroCombatTool;
use App\Services\Mcp\AiChangeSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_forbidden_content_operations_are_rejected(): void
    {
        $validation = app(AiChangeSetService::class)->validateOperations([
            ['type' => 'create_item', 'data' => ['name' => 'Miecz AI']],
        ]);

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
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
            'writing context' => [GetWritingContextTool::class, 'get_writing_context'],
            'dialog graph' => [GetDialogGraphTool::class, 'get_dialog_graph'],
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
}
