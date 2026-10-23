<?php

namespace App\Http\Requests;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use Illuminate\Validation\Rule;

class IndexNpcRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'map_id' => ['nullable', 'integer', $this->existsOnCurrentWorld('maps')],
            'base_npc_id' => ['nullable', 'integer', $this->existsOnCurrentWorld('base_npcs')],
            'dialog_id' => ['nullable', 'integer', $this->existsOnCurrentWorld('dialogs')],
            'has_dialog' => ['nullable', 'boolean'],
            'enabled' => ['nullable', 'boolean'],
            'in_group' => ['nullable', 'boolean'],
            'auto_start_dialog' => ['nullable', 'boolean'],
            'is_aggressive' => ['nullable', 'boolean'],
            'category' => ['nullable', Rule::enum(BaseNpcCategory::class)],
            'rank' => ['nullable', Rule::enum(BaseNpcRank::class)],
            'min_lvl' => ['nullable', 'integer', 'between:0,1000'],
            'max_lvl' => ['nullable', 'integer', 'between:0,1000', Rule::when($this->filled('min_lvl'), ['gte:min_lvl'])],
            'placement' => ['nullable', Rule::in(['none', 'one', 'many'])],
        ];
    }

    public function messages(): array
    {
        return ['max_lvl.gte' => 'Poziom maksymalny musi być równy lub większy od minimalnego.'];
    }
}
