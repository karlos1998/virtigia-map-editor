<?php

namespace App\Http\Requests;

class StoreMapTrackRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dialog_counter_id' => ['required', $this->existsOnCurrentWorld('dialog_counters')],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
