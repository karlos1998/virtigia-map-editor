<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestSearchResource extends JsonResource
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
            'steps' => $this->whenLoaded('steps', fn () => $this->steps->map(fn ($step): array => [
                'id' => $step->id,
                'quest_id' => $step->quest_id,
                'name' => $step->name,
            ])),
        ];
    }
}
