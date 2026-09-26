<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreMapRequest extends FormRequest
{
    private const int TILE_SIZE = 32;

    private const int MAX_TILES_PER_SIDE = 128;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:4',
                'max:50',
            ],
            'img' => [
                'required',
                'string',
                fn (string $attribute, mixed $value, Closure $fail) => $this->validateImage($attribute, $value, $fail),
            ],
            'fileName' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_\-]+\.(png|jpg|jpeg)$/i',
            ],
        ];
    }

    public function name(): string
    {
        return $this->string('name')->toString();
    }

    public function imageDataUri(): string
    {
        return $this->string('img')->toString();
    }

    public function fileName(): string
    {
        return $this->string('fileName')->toString();
    }

    /** @param  Closure(string): void  $fail */
    private function validateImage(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^data:image\/(png|jpeg);base64,/', $value)) {
            $fail('Grafika mapy musi być plikiem PNG albo JPEG zakodowanym jako data URI.');

            return;
        }

        $encodedImage = substr($value, strpos($value, ',') + 1);
        $imageData = base64_decode($encodedImage, true);

        if ($imageData === false) {
            $fail('Nie udało się odczytać danych grafiki mapy.');

            return;
        }

        $imageInfo = @getimagesizefromstring($imageData);

        if ($imageInfo === false) {
            $fail('Przesłane dane nie zawierają prawidłowej grafiki.');

            return;
        }

        [$width, $height] = $imageInfo;
        $mimeType = $imageInfo['mime'] ?? null;

        if (! in_array($mimeType, ['image/png', 'image/jpeg'], true)) {
            $fail('Grafika mapy musi być w formacie PNG albo JPEG.');

            return;
        }

        if ($width % self::TILE_SIZE !== 0 || $height % self::TILE_SIZE !== 0) {
            $fail('Wynikowa grafika mapy musi mieć szerokość i wysokość podzielną przez 32 px.');

            return;
        }

        $maximumPixelsPerSide = self::MAX_TILES_PER_SIDE * self::TILE_SIZE;

        if ($width > $maximumPixelsPerSide || $height > $maximumPixelsPerSide) {
            $fail('Mapa może mieć maksymalnie 128 × 128 pól, czyli 4096 × 4096 px.');
        }
    }
}
