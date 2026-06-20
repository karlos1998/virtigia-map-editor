<?php

namespace App\Http\Requests;

use App\Models\BaseItem;
use App\Models\BaseNpc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAttachBaseItemsToBaseNpcLootRequest extends FormRequest
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
            'base_npc_id' => ['required', 'integer', Rule::exists($this->baseNpcsTable(), 'id')],
            'item_ids' => ['required', 'array', 'min:1', 'max:500'],
            'item_ids.*' => ['integer', 'distinct', Rule::exists($this->baseItemsTable(), 'id')],
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

    private function baseNpcsTable(): string
    {
        $connectionName = (new BaseNpc)->getConnectionName() ?? config('database.default');

        return "{$connectionName}.base_npcs";
    }

    private function baseItemsTable(): string
    {
        $connectionName = (new BaseItem)->getConnectionName() ?? config('database.default');

        return "{$connectionName}.base_items";
    }
}
