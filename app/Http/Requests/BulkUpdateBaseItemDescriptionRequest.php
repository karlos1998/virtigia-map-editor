<?php

namespace App\Http\Requests;

class BulkUpdateBaseItemDescriptionRequest extends CurrentWorldRequest
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
            'item_ids' => ['required', 'array', 'min:1', 'max:500'],
            'item_ids.*' => ['integer', 'distinct', $this->existsOnCurrentWorld('base_items')],
            'search_phrase' => ['required', 'string', 'min:1', 'max:1000'],
            'replacement_phrase' => ['present', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'item_ids.required' => 'Wybierz przynajmniej jeden przedmiot.',
            'item_ids.min' => 'Wybierz przynajmniej jeden przedmiot.',
            'search_phrase.required' => 'Podaj frazę do poprawy.',
            'search_phrase.min' => 'Fraza do poprawy musi mieć przynajmniej 1 znak.',
            'replacement_phrase.present' => 'Podaj frazę docelową.',
        ];
    }
}
