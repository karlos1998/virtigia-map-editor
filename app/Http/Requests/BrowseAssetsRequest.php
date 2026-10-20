<?php

namespace App\Http\Requests;

use App\Rules\AssetPath;
use Illuminate\Foundation\Http\FormRequest;

class BrowseAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAdministratorRole() ?? false;
    }

    public function rules(): array
    {
        return [
            'path' => ['nullable', 'string', new AssetPath],
            'search' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'directories_only' => ['nullable', 'boolean'],
        ];
    }

    public function assetPath(): string
    {
        return (string) ($this->validated('path') ?? '');
    }

    public function searchTerm(): string
    {
        return trim((string) $this->validated('search', ''));
    }

    public function pageNumber(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }
}
