<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Virtigia\ApplyChangeSetTool;
use App\Mcp\Tools\Virtigia\DraftChangeSetTool;
use App\Mcp\Tools\Virtigia\GetWritingContextTool;
use App\Mcp\Tools\Virtigia\ListChangeSetsTool;
use App\Mcp\Tools\Virtigia\ProfileTool;
use App\Mcp\Tools\Virtigia\RevertChangeSetTool;
use App\Mcp\Tools\Virtigia\SearchGameContentTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Virtigia Content Server')]
#[Version('0.1.0')]
#[Instructions('Use search_game_content to resolve exact records and get_writing_context before writing dialogue. Default to Retro when the user did not name another world, but include the world in every write. Draft changes first, show the user the validated change set, and call apply_change_set only after explicit confirmation. Never create BaseNPC records, items, maps, or graphics. New placed NPCs must reference an existing BaseNPC. Every applied change is recorded and can be reverted if no later edit conflicts.')]
class VirtigiaContentServer extends Server
{
    protected array $tools = [
        ProfileTool::class,
        SearchGameContentTool::class,
        GetWritingContextTool::class,
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
