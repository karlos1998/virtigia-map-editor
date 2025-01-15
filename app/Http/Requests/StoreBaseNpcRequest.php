<?php

namespace App\Http\Requests;

use App\Enums\BaseNpcRank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;

class StoreBaseNpcRequest extends FormRequest
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
            'src' => [
                'required', 'string',
                function ($attribute, $value, $fail) {
                    if (Storage::disk('s3')->get("img/npc/{$value}") == null) { //exists cos nie chcialo dzialac...
                        $fail("The {$attribute} path does not exist in S3 storage.");
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
        ];
    }
}
