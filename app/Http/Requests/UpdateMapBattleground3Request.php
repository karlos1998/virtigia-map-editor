<?php

namespace App\Http\Requests;

use App\Services\MapService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;

class UpdateMapBattleground3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|Closure>> */
    public function rules(): array
    {
        return [
            'battleground3' => [
                'bail', 'required', 'string', 'max:255', 'regex:'.MapService::MODERN_BATTLEGROUND_FILENAME_PATTERN,
                function (string $attribute, string $value, Closure $fail): void {
                    if (! Storage::disk('s3')->fileExists(MapService::MODERN_BATTLEGROUND_DIRECTORY.'/'.$value)) {
                        $fail('Wybierz tło Modern dostępne w katalogu grafik.');
                    }
                },
            ],
        ];
    }

    public function battleground3(): string
    {
        return $this->validated('battleground3');
    }
}
