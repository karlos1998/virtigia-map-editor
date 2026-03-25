<?php

namespace App\Http\Resources;

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
            'guides' => $this->guideView?->guides ?? [],
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
