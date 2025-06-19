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
            'auto_progress' => $this->whenLoaded('autoProgress', fn($autoProgress) => [
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
