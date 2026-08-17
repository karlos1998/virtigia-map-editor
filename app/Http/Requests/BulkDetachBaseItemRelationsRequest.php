<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class BulkDetachBaseItemRelationsRequest extends CurrentWorldRequest
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
        $targetType = $this->string('target_type')->toString();
        $targetTable = match ($targetType) {
            'base_npc' => 'base_npcs',
            'shop' => 'shops',
            default => null,
        };

        return [
            'target_type' => ['required', Rule::in(['base_npc', 'shop'])],
            'target_id' => [
                'required',
                'integer',
                ...($targetTable === null ? [] : [$this->existsOnCurrentWorld($targetTable)]),
            ],
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
            'target_type.required' => 'Wybierz typ relacji.',
            'target_id.required' => 'Wybierz Base NPC lub sklep.',
            'item_ids.required' => 'Wybierz przynajmniej jeden przedmiot.',
        ];
    }
}
