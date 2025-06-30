<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDoorLevelRestrictionsRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

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
            'door_ids' => 'required|array',
            'door_ids.*' => ['required', 'integer', "exists:$this->selectedDatabase.doors,id"],
            'min_diff' => 'required|integer|min:0',
            'max_diff' => 'required|integer|min:0',
        ];
    }
}
