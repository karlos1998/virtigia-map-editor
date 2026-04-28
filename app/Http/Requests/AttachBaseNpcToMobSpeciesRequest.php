<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class AttachBaseNpcToMobSpeciesRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_npc_id' => ['required', 'integer', "exists:$this->selectedDatabase.base_npcs,id"],
        ];
    }
}

