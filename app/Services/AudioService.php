<?php

namespace App\Services;

use App\Http\Resources\AudioListResource;
use App\Models\Audio;
use App\Models\BaseItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class AudioService extends BaseService
{
    public function __construct(
        private readonly Audio $audioModel,
    ) {}

    public function getAll()
    {
        return $this->fetchData(
            AudioListResource::class,
            $this->audioModel->newQuery()->orderByDesc('id'),
            new TableService(
                columns: [
                    'id' => new TableTextColumn(sortable: true),
                    'name' => new TableTextColumn(sortable: true),
                    'path' => new TableTextColumn(sortable: true),
                ],
                globalFilterColumns: ['name', 'path'],
            )
        );
    }

    public function searchByName(string $query, int $limit = 30)
    {
        return $this->audioModel->newQuery()
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'path']);
    }

    public function create(array $validated): Audio
    {
        $path = $this->storeAudioFile($validated['file']);

        return $this->audioModel->newQuery()->create([
            'name' => $validated['name'],
            'path' => $path,
        ]);
    }

    public function update(Audio $audio, array $validated): void
    {
        $payload = [
            'name' => $validated['name'],
        ];

        if (! empty($validated['file']) && $validated['file'] instanceof UploadedFile) {
            $payload['path'] = $this->storeAudioFile($validated['file']);
        }

        $audio->update($payload);
    }

    public function findItemsLinkedToAudio(int $audioId): Collection
    {
        return (new BaseItem)->newQuery()
            ->whereRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(attributes, "$.audioId")) AS UNSIGNED) = ?', [$audioId])
            ->orderBy('id')
            ->get();
    }

    private function storeAudioFile(UploadedFile $file): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $fileName = ($name !== '' ? $name : Str::uuid()->toString()).'_'.time().'.'.$extension;
        // Keep audio under the same prefix family as item assets, because some S3 policies
        // allow writes only for selected "img/items/*" paths.
        $directory = 'img/items/audio';

        $path = $directory.'/'.$fileName;
        $binary = file_get_contents($file->getRealPath());
        $uploaded = Storage::disk('s3')->put($path, $binary);
        if ($uploaded === true) {
            return $path;
        }

        throw new RuntimeException(sprintf(
            'Nie udało się zapisać pliku audio w S3 (name=%s, size=%d, mime=%s).',
            $file->getClientOriginalName(),
            (int) $file->getSize(),
            (string) $file->getClientMimeType()
        ));
    }
}
