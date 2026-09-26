<?php

namespace App\Services\Mcp;

use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Map as GameMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class VisualReferenceService
{
    private const MAP_LIMIT = 6;

    private const ASSET_LIMIT = 12;

    public function __construct(
        private readonly McpWorldService $worldService,
    ) {}

    /**
     * @param  array<int, int>  $ids
     * @param  array<string, mixed>  $filters
     * @return array{metadata: array<string, mixed>, images: array<int, array{binary: string, mime_type: string, meta: array<string, mixed>}>}
     */
    public function browse(
        string $world,
        string $kind,
        ?string $query,
        array $ids,
        int $limit,
        array $filters = [],
    ): array {
        $world = $this->worldService->use($world);
        $effectiveLimit = min(max($limit, 1), $kind === 'maps' ? self::MAP_LIMIT : self::ASSET_LIMIT);
        $records = $this->records($kind, $query, $ids, $effectiveLimit, $filters);
        $references = [];
        $images = [];
        $warnings = [];

        foreach ($records as $record) {
            $reference = $this->reference($kind, $record);

            try {
                $binary = Storage::disk('s3')->get($reference['storage_key']);
                $preview = $this->renderPreview($binary, $kind);
                $reference['original_pixel_width'] = $preview['original_width'];
                $reference['original_pixel_height'] = $preview['original_height'];
                $reference['preview_pixel_width'] = $preview['preview_width'];
                $reference['preview_pixel_height'] = $preview['preview_height'];
                $reference['image_index'] = count($images) + 1;
                $images[] = [
                    'binary' => $preview['binary'],
                    'mime_type' => 'image/png',
                    'meta' => [
                        'kind' => $kind,
                        'id' => $reference['id'],
                        'name' => $reference['name'],
                        'image_index' => $reference['image_index'],
                    ],
                ];
            } catch (Throwable $exception) {
                $reference['image_index'] = null;
                $warnings[] = "Nie udało się odczytać grafiki {$kind} #{$reference['id']}: {$exception->getMessage()}";
            }

            unset($reference['storage_key']);
            $references[] = $reference;
        }

        return [
            'metadata' => [
                'world' => $world,
                'kind' => $kind,
                'query' => $query,
                'requested_ids' => $ids,
                'effective_limit' => $effectiveLimit,
                'reference_count' => count($references),
                'image_count' => count($images),
                'references' => $references,
                'warnings' => $warnings,
                'guidance' => [
                    'Images follow the same order as references with a non-null image_index.',
                    'Map images are bounded previews of the original full map, not the very small list thumbnail.',
                    'Item and NPC previews are converted to PNG and enlarged with nearest-neighbour scaling when useful, preserving pixel-art edges.',
                    'Use several related references to infer recurring palette, silhouette, outline, lighting, scale and density. Do not copy one asset verbatim.',
                    'These are per-task references, not persistent model training.',
                ],
            ],
            'images' => $images,
        ];
    }

    /**
     * @param  array<int, int>  $ids
     * @param  array<string, mixed>  $filters
     * @return Collection<int, GameMap|BaseItem|BaseNpc>
     */
    private function records(string $kind, ?string $query, array $ids, int $limit, array $filters): Collection
    {
        $model = match ($kind) {
            'maps' => GameMap::class,
            'items' => BaseItem::class,
            'npcs' => BaseNpc::class,
            default => throw new RuntimeException("Nieobsługiwany rodzaj referencji: {$kind}."),
        };

        /** @var Builder $builder */
        $builder = $model::query();
        $builder
            ->when($ids !== [], fn (Builder $assetQuery) => $assetQuery->whereIn('id', $ids))
            ->when(trim((string) $query) !== '', fn (Builder $assetQuery) => $assetQuery->where('name', 'like', '%'.trim((string) $query).'%'));

        if ($kind === 'items') {
            $builder
                ->when($filters['category'] ?? null, fn (Builder $assetQuery, string $category) => $assetQuery->where('category', $category))
                ->when($filters['rarity'] ?? null, fn (Builder $assetQuery, string $rarity) => $assetQuery->where('rarity', $rarity));
        }

        if ($kind === 'npcs') {
            $builder
                ->when($filters['category'] ?? null, fn (Builder $assetQuery, string $category) => $assetQuery->where('category', $category))
                ->when($filters['rank'] ?? null, fn (Builder $assetQuery, string $rank) => $assetQuery->where('rank', $rank))
                ->when($filters['min_level'] ?? null, fn (Builder $assetQuery, int $level) => $assetQuery->where('lvl', '>=', $level))
                ->when($filters['max_level'] ?? null, fn (Builder $assetQuery, int $level) => $assetQuery->where('lvl', '<=', $level));
        }

        $records = $builder->orderBy('name')->limit($limit)->get();

        if ($ids === []) {
            return $records;
        }

        $order = array_flip(array_map('intval', $ids));

        return $records->sortBy(fn ($record): int => $order[(int) $record->id] ?? PHP_INT_MAX)->values();
    }

    /** @return array<string, mixed> */
    private function reference(string $kind, GameMap|BaseItem|BaseNpc $record): array
    {
        $directory = match ($kind) {
            'maps' => config('assets.dirs.maps'),
            'items' => config('assets.dirs.items'),
            'npcs' => config('assets.dirs.npcs'),
        };
        $path = $this->normalizePath((string) $record->src);
        $reference = [
            'id' => (int) $record->id,
            'name' => (string) $record->name,
            'source_path' => $path,
            'storage_key' => trim((string) $directory, '/').'/'.$path,
        ];

        if ($record instanceof GameMap) {
            return $reference + [
                'map_width_tiles' => (int) $record->x,
                'map_height_tiles' => (int) $record->y,
                'pvp' => $record->pvp?->value ?? $record->pvp,
            ];
        }

        if ($record instanceof BaseItem) {
            return $reference + [
                'category' => $record->category?->value,
                'rarity' => $record->rarity,
                'required_level' => data_get($record->attributes, 'needLevel'),
            ];
        }

        return $reference + [
            'level' => (int) $record->lvl,
            'category' => $record->category?->value,
            'rank' => $record->rank?->value,
            'profession' => $record->profession?->value,
        ];
    }

    /**
     * @return array{binary: string, original_width: int, original_height: int, preview_width: int, preview_height: int}
     */
    private function renderPreview(string $binary, string $kind): array
    {
        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new RuntimeException('format obrazu nie jest obsługiwany przez renderer podglądu');
        }

        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        [$previewWidth, $previewHeight] = $this->previewDimensions($kind, $originalWidth, $originalHeight);
        $preview = imagecreatetruecolor($previewWidth, $previewHeight);
        imagealphablending($preview, false);
        imagesavealpha($preview, true);
        $transparent = imagecolorallocatealpha($preview, 0, 0, 0, 127);
        imagefilledrectangle($preview, 0, 0, $previewWidth, $previewHeight, $transparent);

        if ($kind === 'maps' && ($previewWidth < $originalWidth || $previewHeight < $originalHeight)) {
            imagecopyresampled($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $originalWidth, $originalHeight);
        } else {
            imagecopyresized($preview, $source, 0, 0, 0, 0, $previewWidth, $previewHeight, $originalWidth, $originalHeight);
        }

        ob_start();
        imagepng($preview);
        $previewBinary = ob_get_clean();
        imagedestroy($source);
        imagedestroy($preview);

        if (! is_string($previewBinary)) {
            throw new RuntimeException('nie udało się zakodować podglądu PNG');
        }

        return [
            'binary' => $previewBinary,
            'original_width' => $originalWidth,
            'original_height' => $originalHeight,
            'preview_width' => $previewWidth,
            'preview_height' => $previewHeight,
        ];
    }

    /** @return array{0: int, 1: int} */
    private function previewDimensions(string $kind, int $width, int $height): array
    {
        $longestSide = max($width, $height);

        if ($kind === 'maps') {
            $scale = min(1, 1024 / max(1, $longestSide));

            return [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];
        }

        $targetLongestSide = $kind === 'items' ? 256 : 512;
        $integerScale = max(1, (int) floor($targetLongestSide / max(1, $longestSide)));

        return [$width * $integerScale, $height * $integerScale];
    }

    private function normalizePath(string $path): string
    {
        $normalizedPath = str_replace('imgimg', 'img', trim($path));

        if (filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            $normalizedPath = parse_url($normalizedPath, PHP_URL_PATH) ?: '';
        }

        return ltrim(strtok($normalizedPath, '?') ?: '', '/');
    }
}
