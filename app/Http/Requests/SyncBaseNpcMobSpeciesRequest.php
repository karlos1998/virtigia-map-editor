<?php

namespace App\Http\Requests;

class SyncBaseNpcMobSpeciesRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mob_species_ids' => ['array'],
            'mob_species_ids.*' => ['integer', $this->existsOnCurrentWorld('mob_species')],
        ];
    }
}
