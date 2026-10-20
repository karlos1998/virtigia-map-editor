<?php

namespace App\Http\Requests;

use App\Rules\AssetPath;

class CreateAssetDirectoryRequest extends BrowseAssetsRequest
{
    public function rules(): array
    {
        return [
            'path' => ['nullable', 'string', new AssetPath],
            'name' => ['required', 'string', 'max:255', new AssetPath(singleSegment: true)],
        ];
    }

    public function directoryName(): string
    {
        return $this->validated('name');
    }
}
