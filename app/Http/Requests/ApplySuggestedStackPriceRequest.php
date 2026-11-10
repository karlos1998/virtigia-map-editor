<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplySuggestedStackPriceRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'world' => ['required', 'string'],
            'expected_price' => ['required', 'integer', 'min:1'],
            'expected_quantity' => ['required', 'integer', 'min:2'],
        ];
    }

    public function world(): string
    {
        return (string) $this->validated('world');
    }

    public function expectedPrice(): int
    {
        return (int) $this->validated('expected_price');
    }

    public function expectedQuantity(): int
    {
        return (int) $this->validated('expected_quantity');
    }
}
