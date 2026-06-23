<?php

namespace App\Http\Requests;

class SyncBaseNpcSeasonalEventsRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seasonal_event_ids' => ['array'],
            'seasonal_event_ids.*' => ['integer', $this->existsOnCurrentWorld('seasonal_events')],
        ];
    }
}
