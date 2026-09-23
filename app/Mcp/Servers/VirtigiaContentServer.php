<?php

namespace App\Mcp\Servers;

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
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Virtigia Content Server')]
#[Version('0.1.0')]
#[Instructions('Use search_game_content to resolve exact records and get_writing_context before writing dialogue. Before editing an existing dialog, call get_dialog_graph and use patch_dialog to add or update only the required nodes, options, and edges. A dialog shared by multiple NPCs is intentionally changed for all of them and is not a blocker. Preserve existing shop and hotel nodes. Node positions are laid out automatically after a patch. For Retro combat questions, inspect the real BaseNPC, fetch legal build options, propose equipment and a dependency-correct skill allocation, and verify the proposal with simulate_retro_combat; compare multiple professions/builds when useful. Use analyze_retro_loot for current and cumulative drop chances. These analysis tools are read-only and never modify production MongoDB. Default to Retro when the user did not name another world, but include the world in every write. Draft changes first, show the user the validated change set, and call apply_change_set only after explicit confirmation. Never create BaseNPC records, items, maps, or graphics. New placed NPCs must reference an existing BaseNPC. Every applied change is recorded and can be reverted if no later edit conflicts.')]
class VirtigiaContentServer extends Server
{
    protected array $tools = [
        ProfileTool::class,
        SearchGameContentTool::class,
        GetDialogGraphTool::class,
        GetWritingContextTool::class,
        InspectRetroNpcTool::class,
        GetRetroBuildOptionsTool::class,
        SimulateRetroCombatTool::class,
        AnalyzeRetroLootTool::class,
        DraftChangeSetTool::class,
        ApplyChangeSetTool::class,
        ListChangeSetsTool::class,
        RevertChangeSetTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
