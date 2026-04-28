<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobSpeciesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_npcs' => $this->whenLoaded('baseNpcs', function () {
                return $this->baseNpcs->map(fn ($npc) => [
                    'id' => $npc->id,
                    'name' => $npc->name,
                    'lvl' => $npc->lvl,
                    'profession' => $npc->profession?->value,
                    'src' => \App\Facades\AssetUrl::npc($npc->src),
                ])->values()->all();
            }),
        ];
    }
}
