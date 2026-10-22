<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestResource extends JsonResource
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
            'name' => $this->name,
            'seasonal_events' => $this->whenLoaded('seasonalEvents', fn () => $this->seasonalEvents->map(fn ($event) => [
                'id' => $event->id,
                'name' => $event->name,
                'is_currently_active' => $event->isCurrentlyActive(),
            ])),
            'is_daily' => $this->isDaily(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'steps' => QuestStepResource::collection($this->whenLoaded('steps')),
            'dialogs' => SimpleDialogResource::collection($this->getDialogs()),
            'nodes' => SimpleDialogNodeResource::collection($this->getNodes()),
            'nodeOptions' => SimpleDialogNodeOptionResource::collection($this->getNodeOptions()),
            'edges' => SimpleDialogEdgeResource::collection($this->getEdges()),
        ];
    }
}
