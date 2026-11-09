<?php

namespace App\Http\Requests;

class UpdateBaseNpcLootRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['destroy_on_level_gap' => ['required', 'boolean']];
    }

    public function shouldDestroyOnLevelGap(): bool
    {
        return $this->boolean('destroy_on_level_gap');
    }
}
