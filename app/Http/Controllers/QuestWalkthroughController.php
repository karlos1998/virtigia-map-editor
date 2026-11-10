<?php

namespace App\Http\Controllers;

use App\Facades\AssetUrl;
use App\Models\Quest;
use App\Models\QuestWalkthrough;
use App\Services\QuestWalkthrough\QuestWalkthroughGenerator;
use App\Services\QuestWalkthrough\QuestWalkthroughService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestWalkthroughController extends Controller
{
    public function __construct(private readonly QuestWalkthroughService $service) {}

    public function show(Request $request, Quest $quest): Response
    {
        $connection = $this->connection($request);
        $available = $this->service->isAvailable($connection);
        $record = $available ? QuestWalkthrough::query()->find($quest->id) : null;
        $walkthrough = $record?->walkthrough ?? $this->service->preview($connection, $quest->id);

        return Inertia::render('Quest/Walkthrough', [
            'quest' => ['id' => $quest->id, 'name' => $quest->name, 'updated_at' => $quest->updated_at],
            'walkthrough' => $this->withAssetUrls($walkthrough),
            'meta' => [
                'available' => $available,
                'live_preview' => $record === null,
                'generated_at' => $record?->generated_at,
                'checked_at' => $record?->checked_at,
                'outdated_generator' => $record !== null && $record->generator_version !== QuestWalkthroughGenerator::VERSION,
                'world_status' => $available ? $this->service->status($connection) : [],
            ],
        ]);
    }

    public function regenerate(Request $request, Quest $quest): RedirectResponse
    {
        $connection = $this->connection($request);

        abort_unless($this->service->isAvailable($connection), 409, 'Brak tabeli quest_walkthroughs w tym świecie — uruchom migracje remote.');

        $this->service->refreshQuest($connection, $quest->id);

        return to_route('quests.walkthrough', $quest->id);
    }

    /**
     * Stored graphics are template-relative paths; the page needs public URLs.
     */
    private function withAssetUrls(array $walkthrough): array
    {
        $npc = function (array $npc): array {
            $npc['src'] = AssetUrl::npc($npc['src'] ?? null);

            return $npc;
        };

        foreach ($walkthrough['sections'] ?? [] as $index => $section) {
            $walkthrough['sections'][$index]['npcs'] = array_map($npc, $section['npcs'] ?? []);

            foreach ($section['targets'] ?? [] as $targetIndex => $target) {
                $walkthrough['sections'][$index]['targets'][$targetIndex]['npcs'] = array_map($npc, $target['npcs'] ?? []);
            }
        }

        foreach ($walkthrough['references']['items'] ?? [] as $itemId => $item) {
            $item['src'] = AssetUrl::item($item['src'] ?? null);
            foreach ($item['sources'] ?? [] as $sourceIndex => $source) {
                if (isset($source['npc']['src'])) {
                    $item['sources'][$sourceIndex]['npc']['src'] = AssetUrl::npc($source['npc']['src']);
                }
            }
            $walkthrough['references']['items'][$itemId] = $item;
        }

        return $walkthrough;
    }

    private function connection(Request $request): string
    {
        return $request->attributes->get('world_template')?->connection_name ?? (string) session('world');
    }
}
