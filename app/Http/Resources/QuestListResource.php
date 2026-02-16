<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestListResource extends JsonResource
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
                if ($this->relationLoaded('steps')) {
                    return $this->steps->contains(fn($s) => ($s->auto_advance_next_day ?? false) == true);
                }

                return $this->steps()->where('auto_advance_next_day', true)->exists();
            }),
            'created_at' => $this->created_at,
        ];
    }
}
