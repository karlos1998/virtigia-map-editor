<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Virtigia\AnalyzeRetroLootTool;
use App\Mcp\Tools\Virtigia\ApplyChangeSetTool;
use App\Mcp\Tools\Virtigia\DraftChangeSetTool;
use App\Mcp\Tools\Virtigia\GetBaseItemTool;
use App\Mcp\Tools\Virtigia\GetDialogGraphTool;
use App\Mcp\Tools\Virtigia\GetRetroBuildOptionsTool;
use App\Mcp\Tools\Virtigia\GetShopInventoryTool;
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
#[Version('0.3.0')]
#[Instructions('Use only this Map Editor MCP for Virtigia data and actions. Never call Retro Engine directly, read its credentials or database configuration, inspect a local engine repository for game data, or create local API/database clients. If a required MCP tool is unavailable or fails, report the integration failure instead of bypassing it. Use search_game_content to resolve exact records and get_writing_context before writing dialogue. Before editing an existing dialog, call get_dialog_graph and use patch_dialog to add or update only the required nodes, options, and edges. A dialog shared by multiple NPCs is intentionally changed for all of them and is not a blocker. Preserve existing shop and hotel nodes. Node positions are laid out automatically after a patch. Before cloning or editing a BaseItem call get_base_item and preserve unrelated fields. For a percentage improvement, calculate and show exact before/after values; never guess attribute names. A brand-new BaseItem requires an attached PNG/GIF image exactly 32x32. Before assigning an item to a shop call get_shop_inventory, choose an explicit free position 0-79 (position = row * 8 + column; 8 columns, 10 rows), and include it in the draft. Use @item:key in quest/dialog actions to reference an item created in the same commit. For Retro combat questions, inspect the real BaseNPC, fetch legal and obtainable build options with source filters, propose equipment and a dependency-correct skill allocation, and verify the proposal with simulate_retro_combat; compare multiple professions and elemental builds. Never choose an element from resistance alone: include attack-speed slowdown, freeze, shields, active skills, weapon/off-hand rules and the simulation model limitations. A two-handed weapon or staff cannot be combined with a shield or auxiliary item. Use analyze_retro_loot for current and cumulative drop chances. These analysis tools are read-only and never modify production MongoDB. Default to Retro when the user did not name another world, but include the world in every write. Draft changes first, show the user the validated change set including item attributes and shop positions, and call apply_change_set only after explicit confirmation. Never create BaseNPC records or maps. New placed NPCs must reference an existing BaseNPC. Every applied change is recorded and can be reverted if no later edit conflicts.')]
class VirtigiaContentServer extends Server
{
    protected array $tools = [
        ProfileTool::class,
        SearchGameContentTool::class,
        GetBaseItemTool::class,
        GetShopInventoryTool::class,
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
