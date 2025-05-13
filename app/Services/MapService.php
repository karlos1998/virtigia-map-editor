<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Models\Map;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class MapService extends BaseService
{
    public function __construct(
        private readonly Map $mapModel,
        private readonly AssetService $assetService
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            MapResource::class,
            $this->mapModel,
            new TableService(
                columns: [
                  'id' => new TableTextColumn(
                      placeholder: 'Wpisz szukane id',
                      sortable: true,
                  )
                ],
                globalFilterColumns: ['name', 'src']
            )
        );
    }

    public function search(string $search = ''): Collection
    {
        return $this->mapModel->where('name', 'like', '%' . $search . '%')->limit(10)->get();
    }

    /**
     * @throws ValidationException
     */
    public function store(string $imgBase64, string $fileName, string $name)
    {
        $imageData = $this->assetService->storeFromBase64('img/locations/retro/', $imgBase64, $fileName);
        $width = $imageData['width'] / 32;
        $height = $imageData['height'] / 32;

        return $this->mapModel->create([
            'name' => $name,
            'src' => "retro/$fileName",
            'x' => $width,
            'y' => $height,
            'col' => str_repeat('0', $width * $height),
            'battleground' => 'farmvillage01.jpg',
            'water' => '',
            'pvp' => 0,
        ]);
    }

    public function updateCol(Map $map, string $col)
    {
        $map->update(['col' => $col]);
    }

    public function updateName(Map $map, string $name)
    {
        $map->update(['name' => $name]);
    }

    public function updatePvp(Map $map, int $pvp)
    {
        $map->update(['pvp' => $pvp]);
    }

    public function updateRespawnPoint(Map $map, int $respawnPointId)
    {
        $map->respawnPoint()->associate($respawnPointId);
        $map->save();
    }

    public function updateWater(Map $map, ?string $water)
    {
        $map->update(['water' => $water ?? '']);
    }
}
