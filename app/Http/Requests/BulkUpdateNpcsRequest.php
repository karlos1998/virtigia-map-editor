<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class BulkUpdateNpcsRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'npc_ids' => ['required', 'array', 'min:1', 'max:500'],
            'npc_ids.*' => ['required', 'integer', 'distinct', $this->existsOnCurrentWorld('npcs')],
            'operation' => ['required', Rule::in(['assign_dialog', 'detach_dialog', 'enable', 'disable', 'enable_auto_dialog', 'disable_auto_dialog', 'enable_aggression', 'disable_aggression'])],
            'dialog_id' => ['nullable', Rule::requiredIf($this->input('operation') === 'assign_dialog'), 'integer', $this->existsOnCurrentWorld('dialogs')],
            'assignment_mode' => ['nullable', Rule::requiredIf($this->input('operation') === 'assign_dialog'), Rule::in(['empty', 'replace'])],
        ];
    }

    public function messages(): array
    {
        return [
            'npc_ids.required' => 'Zaznacz przynajmniej jedną instancję NPC.',
            'npc_ids.max' => 'Jednorazowo można zmienić maksymalnie 500 instancji.',
            'dialog_id.required' => 'Wybierz dialog do przypisania.',
            'assignment_mode.required' => 'Wybierz sposób przypisania dialogu.',
        ];
    }
}
