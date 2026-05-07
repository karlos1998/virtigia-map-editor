<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class AddWorldMinimapNodeRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function rules(): array
    {
        return [
            'map_id' => ['required', 'integer', 'exists:' . $this->selectedDatabase . '.maps,id'],
            'near_map_id' => ['nullable', 'integer', 'exists:' . $this->selectedDatabase . '.maps,id'],
        ];
    }
}

