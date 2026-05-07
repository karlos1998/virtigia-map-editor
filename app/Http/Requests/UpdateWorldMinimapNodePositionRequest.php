<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorldMinimapNodePositionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'x' => ['required', 'integer'],
            'y' => ['required', 'integer'],
        ];
    }
}

