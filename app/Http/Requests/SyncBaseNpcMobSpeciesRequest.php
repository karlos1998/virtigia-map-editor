<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class SyncBaseNpcMobSpeciesRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mob_species_ids' => ['array'],
            'mob_species_ids.*' => ['integer', "exists:$this->selectedDatabase.mob_species,id"],
        ];
    }
}

