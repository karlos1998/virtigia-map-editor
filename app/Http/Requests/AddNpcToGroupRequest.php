<?php

namespace App\Http\Requests;

class AddNpcToGroupRequest extends CurrentWorldRequest
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
            'source_npc_id' => ['required', $this->existsOnCurrentWorld('npcs')],
            'target_npc_id' => ['required', $this->existsOnCurrentWorld('npcs')],
        ];
    }
}
