<?php

namespace App\Http\Requests;

class UpdateDoorLevelRestrictionsRequest extends CurrentWorldRequest
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
            'door_ids' => 'required|array',
            'door_ids.*' => ['required', 'integer', $this->existsOnCurrentWorld('doors')],
            'min_diff' => 'required|integer|min:0',
            'max_diff' => 'required|integer|min:0',
        ];
    }
}
