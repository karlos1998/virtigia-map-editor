<?php

namespace App\Http\Requests;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateBaseItemRequest extends FormRequest
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
            'name' => [
                'required',
                'min:4',
                'max:50',
            ],

            'category' => [
                'required',
                new Enum(BaseItemCategory::class),
            ],

            'rarity' => [
                'required',
                new Enum(BaseItemRarity::class),
            ],

            'price' => [
                'required',
                'integer',
                'max:1000000000'
            ],

            'currency' => [
                'required',
                new Enum(BaseItemCurrency::class),
            ],

            'image' => [
                'required',
                'string', // Base64 encoded image
                function ($attribute, $value, $fail) {
                    // Check if it's a valid base64 image format (PNG or GIF)
                    if (!preg_match('/^data:image\/(png|gif);base64,/', $value)) {
                        $fail('Obraz musi być w formacie PNG lub GIF.');
                        return;
                    }

                    // Decode base64 to check dimensions
                    $imageData = substr($value, strpos($value, ',') + 1);
                    $decodedImage = base64_decode($imageData);

                    if (!$decodedImage) {
                        $fail('Nieprawidłowy format obrazu.');
                        return;
                    }

                    $imageInfo = getimagesizefromstring($decodedImage);

                    if (!$imageInfo) {
                        $fail('Nie można odczytać wymiarów obrazu.');
                        return;
                    }

                    [$width, $height] = $imageInfo;

                    if ($width !== 32 || $height !== 32) {
                        $fail('Obraz musi mieć wymiary 32x32 pikseli.');
                    }
                },
            ],
        ];
    }
}
