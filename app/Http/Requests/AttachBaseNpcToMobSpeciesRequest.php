<?php

namespace App\Http\Requests;

class AttachBaseNpcToMobSpeciesRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_npc_id' => ['required', 'integer', $this->existsOnCurrentWorld('base_npcs')],
        ];
    }
}
