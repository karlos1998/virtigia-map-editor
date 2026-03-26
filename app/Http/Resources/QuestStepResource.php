<?php

namespace App\Http\Resources;

use App\Facades\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestStepResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $guides = collect($this->guideView?->guides ?? [])
            ->map(function (array $guide): array {
                $guide['npcs'] = collect($guide['npcs'] ?? [])
                    ->map(function (array $npc): array {
                        $npc['src'] = AssetUrl::npc($npc['src'] ?? null);

                        return $npc;
                    })
                    ->values()
                    ->all();

                $guide['click_steps'] = collect($guide['click_steps'] ?? [])
                    ->map(function (array $clickStep): array {
                        $clickStep['option_requirements'] = collect($clickStep['option_requirements'] ?? [])
                            ->map(function (array $requirement): array {
                                if (! isset($requirement['item']) || ! is_array($requirement['item'])) {
                                    return $requirement;
                                }

                                $requirement['item']['src'] = AssetUrl::item($requirement['item']['src'] ?? null);
                                $requirement['item']['usage_sources'] = collect($requirement['item']['usage_sources'] ?? [])
                                    ->map(function (array $source): array {
                                        if (isset($source['npc']) && is_array($source['npc'])) {
                                            $source['npc']['src'] = AssetUrl::npc($source['npc']['src'] ?? null);
                                        }

                                        return $source;
                                    })
                                    ->values()
                                    ->all();

                                return $requirement;
                            })
                            ->values()
                            ->all();

                        return $clickStep;
                    })
                    ->values()
                    ->all();

                return $guide;
            })
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'quest_id' => $this->quest_id,
            'name' => $this->name,
            'description' => $this->description,
            'visible_in_quest_list' => $this->visible_in_quest_list,
            'auto_advance_next_day' => $this->auto_advance_next_day,
            'auto_advance_to_step_id' => $this->auto_advance_to_step_id,
            'auto_progress' => $this->whenLoaded('autoProgress', fn ($autoProgress) => [
                'type' => $autoProgress->type,
                'time_seconds' => $autoProgress->time_seconds,
                'mobs' => $autoProgress->mobs->map(function ($mob) {
                    return [
                        'base_npc_id' => $mob->base_npc_id,
                        'quantity' => $mob->quantity,
                        'base_npc' => $mob->baseNpc,
                    ];
                }),
            ]),
            'guides' => $guides,
            'guide_count' => $this->guideView?->guide_count ?? 0,
            'dialogs' => SimpleDialogResource::collection($this->getDialogs()),
            'nodes' => SimpleDialogNodeResource::collection($this->getNodes()),
            'nodeOptions' => SimpleDialogNodeOptionResource::collection($this->getNodeOptions()),
            'edges' => SimpleDialogEdgeResource::collection($this->getEdges()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
