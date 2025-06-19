<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestStepRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'visible_in_quest_list' => 'boolean',
            'auto_progress' => 'boolean',
            'progress_type' => 'nullable|string|in:time,mobs',
            'progress_time' => 'nullable|integer|min:0',
            'progress_mobs' => 'nullable|array',
            'progress_mobs.*.base_npc_id' => 'required|integer|exists:' . $this->selectedDatabase . '.base_npcs,id',
            'progress_mobs.*.quantity' => 'required|integer|min:1',
        ];
    }
}
