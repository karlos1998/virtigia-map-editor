<?php

namespace App\Http\Requests;

use App\Rules\AssetPath;
use Illuminate\Validation\Rule;

class AssetOperationRequest extends BrowseAssetsRequest
{
    public function rules(): array
    {
        return [
            'path' => ['required', 'string', new AssetPath],
            'type' => ['required', Rule::in(['file', 'directory'])],
            'operation' => ['required', Rule::in(['copy', 'move', 'delete', 'archive'])],
            'destination' => ['required_if:operation,copy,move', 'nullable', 'string', new AssetPath],
        ];
    }

    /** @return array{path: string, type: string, operation: string, destination?: string|null} */
    public function operationData(): array
    {
        return $this->validated();
    }
}
