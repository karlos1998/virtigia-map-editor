<?php

namespace App\Http\Requests;

class UpdateQuestStepRequest extends CurrentWorldRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visible_in_quest_list' => 'boolean',
            'auto_advance_next_day' => 'boolean',
            'auto_advance_to_step_id' => [
                'nullable',
                'integer',
                $this->existsOnCurrentWorld('quest_steps')->where(function ($query) {
                    $quest = $this->route('quest');
                    if ($quest) {
                        $query->where('quest_id', $quest->id);
                    }
                }),
            ],
            'auto_progress' => 'boolean',
            'progress_type' => 'nullable|string|in:time,mobs',
            'progress_time' => 'nullable|integer|min:0',
            'progress_mobs' => 'nullable|array',
            'progress_mobs.*.type' => 'required|string|in:base_npc,mob_species',
            'progress_mobs.*.base_npc_id' => ['nullable', 'integer', $this->existsOnCurrentWorld('base_npcs')],
            'progress_mobs.*.mob_species_id' => ['nullable', 'integer', $this->existsOnCurrentWorld('mob_species')],
            'progress_mobs.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $mobs = $this->input('progress_mobs', []);
            if (! is_array($mobs)) {
                return;
            }

            foreach ($mobs as $index => $mob) {
                $type = $mob['type'] ?? null;

                if ($type === 'base_npc' && empty($mob['base_npc_id'])) {
                    $validator->errors()->add("progress_mobs.$index.base_npc_id", 'Dla typu base_npc wymagane jest base_npc_id.');
                }

                if ($type === 'mob_species' && empty($mob['mob_species_id'])) {
                    $validator->errors()->add("progress_mobs.$index.mob_species_id", 'Dla typu mob_species wymagane jest mob_species_id.');
                }
            }
        });
    }
}
