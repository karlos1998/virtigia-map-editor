<?php

namespace App\Http\Requests\Api;

use App\Models\Npc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DialogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'npc_id' => ['nullable', 'integer', Rule::exists(Npc::class, 'id')],
            'npc_base_npc_id' => ['nullable', 'integer'],
            'node_type' => ['nullable', 'string', 'max:50'],
            'has_npcs' => ['nullable', 'boolean'],
            'has_nodes' => ['nullable', 'boolean'],
            'has_edges' => ['nullable', 'boolean'],
            'updated_from' => ['nullable', 'date'],
            'updated_to' => ['nullable', 'date', 'after_or_equal:updated_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['id', 'name', 'created_at', 'updated_at', 'npcs_count', 'nodes_count', 'edges_count'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
