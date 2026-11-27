<?php

namespace App\Rules;

use App\Models\Audio;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\ProhibitedIf;

final class DialogNodeEffectRules
{
    /** @return array<string, list<string|Exists|ProhibitedIf>> */
    public static function for(string $path, bool $canDisplayEffects): array
    {
        return [
            $path => ['nullable', 'array:shake,dim,sound', Rule::prohibitedIf(! $canDisplayEffects)],
            "$path.shake" => ['nullable', 'array:durationMs,intensity', 'required_array_keys:durationMs,intensity'],
            "$path.shake.durationMs" => ["required_with:$path.shake", 'integer', 'between:100,5000'],
            "$path.shake.intensity" => ["required_with:$path.shake", 'integer', 'between:1,32'],
            "$path.dim" => ['nullable', 'array:durationMs,opacity,holdMs', 'required_array_keys:durationMs,opacity'],
            "$path.dim.durationMs" => ["required_with:$path.dim", 'integer', 'between:100,5000'],
            "$path.dim.opacity" => ["required_with:$path.dim", 'integer', 'between:1,100'],
            "$path.dim.holdMs" => ['nullable', 'integer', 'between:0,10000'],
            "$path.sound" => ['nullable', 'array:audioId', 'required_array_keys:audioId'],
            "$path.sound.audioId" => ['bail', "required_with:$path.sound", 'integer', 'min:1', Rule::exists(Audio::class, 'id')],
        ];
    }
}
