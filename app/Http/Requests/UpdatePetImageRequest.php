<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePetImageRequest extends FormRequest
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

                    $width = $imageInfo[0];
                    $height = $imageInfo[1];

                    // Validation: width divisible by 2, height divisible by 4
                    if ($width % 2 !== 0) {
                        return $fail("The image width ({$width}px) must be divisible by 2.");
                    }

                    if ($height % 4 !== 0) {
                        return $fail("The image height ({$height}px) must be divisible by 4.");
                    }
                },
            ],

            'name' => [
                'nullable',
                'string',
            ]
        ];
    }
}
