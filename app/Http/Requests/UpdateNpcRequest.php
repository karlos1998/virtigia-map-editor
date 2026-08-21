<?php

namespace App\Http\Requests;

class UpdateNpcRequest extends CurrentWorldRequest
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
            'dialog' => [
                'nullable',
                $this->existsOnCurrentWorld('dialogs'),
            ],
            'auto_start_dialog' => ['sometimes', 'boolean'],
            'auto_start_dialog_range' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }
}
