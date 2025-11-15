<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestStepRequest extends FormRequest
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
            'description' => 'required|string',
            'visible_in_quest_list' => 'boolean',
            'auto_advance_next_day' => 'boolean',
            'auto_advance_to_step_id' => [
                'nullable',
                'integer',
                Rule::exists('quest_steps', 'id')->where(function ($query) {
                    $quest = $this->route('quest');
                    if ($quest) {
                        $query->where('quest_id', $quest->id);
                    }
                }),
            ],
        ];
    }
}
