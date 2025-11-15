<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day' => $this->day,
            'month' => $this->month,
            'year' => $this->year,
            'name' => $this->name,
            'items' => RewardCalendarItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
