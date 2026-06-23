<?php

namespace App\Http\Requests;

class AddWorldMinimapNodeRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'map_id' => ['required', 'integer', $this->existsOnCurrentWorld('maps')],
            'near_map_id' => ['nullable', 'integer', $this->existsOnCurrentWorld('maps')],
        ];
    }
}
