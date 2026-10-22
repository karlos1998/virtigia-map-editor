<?php

namespace App\Http\Requests;

class SyncQuestSeasonalEventsRequest extends CurrentWorldRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'seasonal_event_ids' => ['present', 'array'],
            'seasonal_event_ids.*' => ['integer', 'distinct', $this->existsOnCurrentWorld('seasonal_events')],
        ];
    }
}
