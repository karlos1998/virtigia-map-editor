<?php

namespace App\Http\Requests;

class CreateNpcGroupRequest extends CurrentWorldRequest
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
            'npc_ids' => ['required', 'array', 'min:2'],
            'npc_ids.*' => [$this->existsOnCurrentWorld('npcs')],
        ];
    }
}
