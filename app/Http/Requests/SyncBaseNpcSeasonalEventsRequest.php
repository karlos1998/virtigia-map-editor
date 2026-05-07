<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class SyncBaseNpcSeasonalEventsRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seasonal_event_ids' => ['array'],
            'seasonal_event_ids.*' => ['integer', "exists:$this->selectedDatabase.seasonal_events,id"],
        ];
    }
}

