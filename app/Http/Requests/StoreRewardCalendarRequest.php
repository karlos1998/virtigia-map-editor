<?php

namespace App\Http\Requests;

use App\Enums\RewardCalendarStyle;
use Illuminate\Validation\Rule;

class StoreRewardCalendarRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'style' => ['required', Rule::enum(RewardCalendarStyle::class)],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
