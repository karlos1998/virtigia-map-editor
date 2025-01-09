<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'min:4',
                'max:50',
            ],
            'img' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $base64Data = explode(',', $value)[1] ?? null;

                    if (!$base64Data) {
                        $fail("The $attribute field must contain a valid base64-encoded image.");
                        return;
                    }

                    $imageData = base64_decode($base64Data);
                    $image = @imagecreatefromstring($imageData);

                    if (!$image) {
                        $fail("The $attribute field must contain a valid image.");
                        return;
                    }

                    $width = imagesx($image);
                    $height = imagesy($image);

                    if ($width % 32 !== 0 || $height % 32 !== 0) {
                        $fail("The $attribute image dimensions must be divisible by 32.");
                    }

                    $mimeType = finfo_buffer(finfo_open(), $imageData, FILEINFO_MIME_TYPE);
                    if (!in_array($mimeType, ['image/png', 'image/jpeg'])) {
                        $fail("The $attribute field must contain a PNG or JPEG image.");
                    }
                },
            ],
            'fileName' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_\-]+\.(png|jpg|jpeg)$/i',
            ],
        ];
    }
}
