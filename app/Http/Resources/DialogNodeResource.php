<?php

namespace App\Http\Resources;

use App\Models\Map;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DialogNodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'position' => $this->resource->position,
            $this->mergeWhen($this->resource->type == 'special', fn() => [
                'data' => [
                    'dialog_id' => $this->resource->source_dialog_id,
                    'label' => 'Nazwa Npc',
                    'content' => $this->resource->content,
                    'options' => DialogNodeOptionResource::collection($this->resource->options),
                    'action_data' => $this->resource->action_data,
                ]
            ]),

            $this->mergeWhen($this->resource->type == 'shop', fn() => [
                'data' => [
                    'dialog_id' => $this->resource->source_dialog_id,
                    'shop' => $this->resource->shop,
                ]
            ]),

            $this->mergeWhen($this->resource->type == 'teleportation', function(){
                $teleportation = $this->resource->action_data['teleportation'] ?? null;

                if($teleportation){
                    $teleportation['mapName'] = Map::find($teleportation['mapId'])->name;
                }

                return [
                    'data' => [
                        'dialog_id' => $this->resource->source_dialog_id,
                        'action_data' => [
                            'teleportation' => $teleportation,
                        ],
                    ]
                ];
            })
        ];
    }
}
