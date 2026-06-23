<?php

namespace App\Http\Requests;

class UpdateSeasonalEventRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventId = $this->route('seasonalEvent')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $this->uniqueOnCurrentWorld('seasonal_events', 'slug')->ignore($eventId)],
            'active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
