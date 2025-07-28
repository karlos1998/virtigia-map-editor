<?php

namespace App\Http\Requests;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSimpleBaseNpcRequest extends FormRequest
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
            'image' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^data:image\/(png|gif);base64,/', $value, $matches)) {
                        return $fail('The image must be a valid PNG or GIF base64-encoded string.');
                    }

                    $imageData = substr($value, strpos($value, ',') + 1);
                    $decodedImage = base64_decode($imageData);

                    if ($decodedImage === false) {
                        return $fail('Invalid base64 encoding.');
                    }

                    $imageInfo = getimagesizefromstring($decodedImage);

                    if (!$imageInfo || !in_array($imageInfo['mime'], ['image/png', 'image/gif'])) {
                        return $fail('The image must be a PNG or GIF.');
                    }

                    if ($imageInfo[0] > 192 || $imageInfo[1] > 192) {
                        return $fail("The image dimensions ({$imageInfo[0]}x{$imageInfo[1]}) exceed the maximum allowed size of 192x192 pixels.");
                    }
                },
            ],
            'name' => [
                'required',
                'min:2',
                'string'
            ],
            'lvl' => ['required', 'integer'],
            'rank' => ['required', new Enum(BaseNpcRank::class)],
            'category' => ['required', new Enum(BaseNpcCategory::class)],
        ];
    }
}
