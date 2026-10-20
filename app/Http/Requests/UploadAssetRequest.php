<?php

namespace App\Http\Requests;

use App\Rules\AssetPath;
use Illuminate\Http\UploadedFile;

class UploadAssetRequest extends BrowseAssetsRequest
{
    public function rules(): array
    {
        return [
            'path' => ['nullable', 'string', new AssetPath],
            'file' => ['required', 'file', 'max:'.config('asset_manager.max_upload_kb')],
        ];
    }

    public function assetFile(): UploadedFile
    {
        return $this->validated('file');
    }

    public function messages(): array
    {
        return ['file.max' => 'Plik przekracza dozwolony rozmiar.', 'file.uploaded' => 'Serwer nie przyjął pliku. Sprawdź limit rozmiaru wysyłanych plików.'];
    }
}
