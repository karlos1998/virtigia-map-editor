<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Virtigia\AnalyzeRetroLootTool;
use App\Mcp\Tools\Virtigia\ApplyChangeSetTool;
use App\Mcp\Tools\Virtigia\BrowseVisualReferencesTool;
use App\Mcp\Tools\Virtigia\DraftChangeSetTool;
use App\Mcp\Tools\Virtigia\GetBaseItemTool;
use App\Mcp\Tools\Virtigia\GetDialogCapabilitiesTool;
use App\Mcp\Tools\Virtigia\GetDialogGraphTool;
use App\Mcp\Tools\Virtigia\GetQuestTool;
use App\Mcp\Tools\Virtigia\GetRetroBuildOptionsTool;
use App\Mcp\Tools\Virtigia\GetShopInventoryTool;
use App\Mcp\Tools\Virtigia\GetWritingContextTool;
use App\Mcp\Tools\Virtigia\InspectMapTool;
use App\Mcp\Tools\Virtigia\InspectMapTransitionsTool;
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
#[Version('0.7.0')]
#[Instructions('Use only this Map Editor MCP for Virtigia data and actions. Never call Retro Engine directly, read its credentials or database configuration, inspect a local engine repository for game data, or create local API/database clients. If a required MCP tool is unavailable or fails, report the integration failure instead of bypassing it. Use search_game_content to resolve exact records and get_writing_context before writing dialogue. Use browse_visual_references whenever a request depends on the appearance or style of maps, items or NPCs. Inspect several related references and compare recurring palette, silhouette, outlines, lighting, scale, transparency and visual density; do not copy one graphic verbatim and do not claim that viewing references permanently trains the model. Before changing an existing map or its collisions call inspect_map. Collision is one row-major string of exactly width × height characters: 0 is walkable, 1 is blocked, and tile (x,y) uses index y × width + x; rows run left-to-right and then top-to-bottom with no separators. A new map requires a supplied final PNG/JPEG whose pixel dimensions are divisible by 32 and no side exceeds 4096 px; never invent its graphic. Before creating, editing, or deleting a map transition call inspect_map_transitions for every affected map. Treat each transition record as one directed edge: a return passage is a separate record, never an inferred flag. Use exact zero-based source and destination coordinates, keep them inside map bounds, preserve per-direction level and item requirements unless asked to change them, and inspect hotel_room before deletion. For a bidirectional passage draft two explicit operations and verify that each direction lands on the other transition tile. Quest descriptions are player-facing text only and never implement progression. Every kill objective must use steps[].auto_progress with type=mobs and exact BaseNPC or mob-species IDs and quantities; every timed objective must use type=time and time_seconds. Order steps so automatic progression leads to the intended next step. Before repairing an existing quest call get_quest, use patch_quest, then call get_quest again after apply and verify every target, quantity and transition. Never invent quest fields such as kill_targets or on_complete_step_key. Before authoring a non-trivial dialog call get_dialog_capabilities. Before editing an existing dialog also call get_dialog_graph and use patch_dialog to add or update only the required nodes, options, and edges. Distinguish option.rules (whether an answer is visible/clickable) from edge.rules (which connected branch is selected). Use messageContent for a typed player answer, action_data.focus for camera direction, and the exact node/action/rule schemas returned by get_dialog_capabilities. A dialog shared by multiple NPCs is intentionally changed for all of them and is not a blocker. Preserve existing shop and hotel nodes. Node positions are laid out automatically after a patch. Before cloning or editing a BaseItem call get_base_item and preserve unrelated fields. For a percentage improvement, calculate and show exact before/after values; never guess attribute names. A brand-new BaseItem requires an attached PNG/GIF image exactly 32x32. Before designing or evaluating a new item image, browse multiple existing items of the same category and rarity. Before assigning an item to a shop call get_shop_inventory, choose an explicit free position 0-79 (position = row * 8 + column; 8 columns, 10 rows), and include it in the draft. Use @item:key in quest/dialog actions to reference an item created in the same commit. For Retro combat questions, inspect the real BaseNPC, fetch legal and obtainable build options with source filters, propose equipment and a dependency-correct skill allocation, and verify the proposal with simulate_retro_combat; compare multiple professions and elemental builds. Never choose an element from resistance alone: include attack-speed slowdown, freeze, shields, active skills, weapon/off-hand rules and the simulation model limitations. A two-handed weapon or staff cannot be combined with a shield or auxiliary item. Use analyze_retro_loot for current and cumulative drop chances. These analysis tools are read-only and never modify production MongoDB. Default to Retro when the user did not name another world, but include the world in every write. Draft changes first, show the user the validated change set including map-transition directions and requirements, quest progression, dialog routing/actions, item attributes and shop positions, and call apply_change_set only after explicit confirmation. A new BaseNPC requires a supplied PNG/GIF up to 230×230 px; never invent its graphic. PNG is static and GIF animation is played as the whole file, not a directional sprite sheet. BaseNPC type 0 is interactive and blocks its occupied tile; type 4 is a non-interactive, non-blocking decorative layer. facing is 0 south, 1 north, 2 west, 3 east. A placed NPC may reference an existing BaseNPC or a BaseNPC created earlier in the same commit. Every applied change is recorded and can be reverted if no later edit conflicts.')]
class VirtigiaContentServer extends Server
{
    protected array $tools = [
        ProfileTool::class,
        SearchGameContentTool::class,
        BrowseVisualReferencesTool::class,
        InspectMapTool::class,
        InspectMapTransitionsTool::class,
        GetQuestTool::class,
        GetBaseItemTool::class,
        GetShopInventoryTool::class,
        GetDialogCapabilitiesTool::class,
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
