<?php

namespace App\Http\Resources;

use App\Http\Resources\SimpleDialogResource;
use App\Http\Resources\SimpleDialogEdgeResource;
use App\Http\Resources\SimpleDialogNodeResource;
use App\Http\Resources\SimpleDialogNodeOptionResource;
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
            'is_daily' => $this->when(true, function () {
                // If steps are loaded, use collection check; otherwise query existence to avoid N+1 in list
                if ($this->relationLoaded('steps')) {
                    return $this->steps->contains(fn($s) => ($s->auto_advance_next_day ?? false) === true);
                }

                return $this->steps()->where('auto_advance_next_day', true)->exists();
            }),
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
