<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobSpeciesListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseNpcs = $this->whenLoaded('baseNpcs', fn () => $this->baseNpcs);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_npcs_count' => $this->baseNpcs()->count(),
            'base_npcs_preview' => $this->whenLoaded('baseNpcs', function () use ($baseNpcs) {
                return $baseNpcs
                    ->take(8)
                    ->map(fn ($npc) => sprintf('%s (%d%s)', $npc->name, $npc->lvl, $npc->profession?->value))
                    ->values()
                    ->all();
            }),
        ];
    }
}

