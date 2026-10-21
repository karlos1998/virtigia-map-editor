<?php

namespace App\Http\Requests;

use App\Rules\PlayingCardAttributes;
use Illuminate\Foundation\Http\FormRequest;

class GeneratePlayingCardArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return ['card' => ['required', new PlayingCardAttributes]];
    }
}
