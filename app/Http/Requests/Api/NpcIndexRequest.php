<?php

namespace App\Http\Requests\Api;

use App\Models\BaseNpc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NpcIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'base_npc_id' => ['nullable', 'integer', Rule::exists(BaseNpc::class, 'id')],
        ];
    }
}
