<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapBattleground3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'battleground3' => ['required', 'string', 'regex:/\Am(?:[1-9]|1[0-9]|2[0-7])\.jpg\z/'],
        ];
    }

    public function battleground3(): string
    {
        return $this->validated('battleground3');
    }
}
