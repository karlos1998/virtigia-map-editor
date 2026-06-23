<?php

namespace App\Http\Requests;

class BulkAttachBaseItemsToBaseNpcLootRequest extends CurrentWorldRequest
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
            'base_npc_id' => ['required', 'integer', $this->existsOnCurrentWorld('base_npcs')],
            'item_ids' => ['required', 'array', 'min:1', 'max:500'],
            'item_ids.*' => ['integer', 'distinct', $this->existsOnCurrentWorld('base_items')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'base_npc_id.required' => 'Wybierz Base NPC.',
            'base_npc_id.exists' => 'Wybrany Base NPC nie istnieje.',
            'item_ids.required' => 'Wybierz przynajmniej jeden przedmiot.',
            'item_ids.min' => 'Wybierz przynajmniej jeden przedmiot.',
            'item_ids.*.exists' => 'Jeden z wybranych przedmiotów nie istnieje.',
        ];
    }
}
