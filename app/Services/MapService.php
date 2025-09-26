<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Models\Map;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class MapService extends BaseService
{
    public function __construct(
        private readonly Map $mapModel,
        private readonly AssetService $assetService,
        private readonly RespawnPointService $respawnPointService,
    )
    {
    }

    /**
     * Get dialog nodes that teleport to the specified map
     *
     * @param Map $map
     * @return Collection
     */
    public function getDialogNodesTeleportingToMap(Map $map): Collection
    {
        return \App\Models\DialogNode::with('dialog')
            ->where('type', 'teleportation')
            ->whereRaw('JSON_EXTRACT(action_data, "$.teleportation.mapId") = ?', [$map->id])
            ->get();
    }

    /**
     * Get base items that teleport to the specified map
     *
     * @param Map $map
     * @return Collection
     */
    public function getItemsTeleportingToMap(Map $map): Collection
    {
        return \App\Models\BaseItem::whereRaw('JSON_EXTRACT(attributes, "$.teleportTo[0]") = ?', [$map->id])
            ->get();
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
                  ),
                    'respawn_point' => new TableDropdownColumn(
                        placeholder: 'Punkt odrodzenia',
                        options: $this->respawnPointService->getRespawnPointsSelectOptions(),
                        sortable: true,
                        sortPath: 'respawn_point_id',
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
        $imageData = $this->assetService->storeFromBase64('img/locations/' . session("world") . '/', $imgBase64, $fileName);
        $width = $imageData['width'] / 32;
        $height = $imageData['height'] / 32;

        return $this->mapModel->create([
            'name' => $name,
            'src' => session("world") . "/$fileName",
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

    public function clearCollisions(Map $map)
    {
        $map->update(['col' => str_repeat('0', $map->x * $map->y)]);
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

    public function updateBattleground(Map $map, string $battleground)
    {
        $map->update(['battleground' => $battleground]);
    }

    public function updateBattleground2(Map $map, ?string $battleground2): void
    {
        $map->update(['battleground2' => $battleground2]);
    }

    public function updateWater(Map $map, ?string $water)
    {
        $map->update(['water' => $water ?? '']);
    }

    /**
     * Update the map image
     *
     * @param Map $map
     * @param string $imgBase64
     * @param string $fileName
     * @return void
     * @throws ValidationException
     * @throws \Exception
     */
    public function updateImage(Map $map, string $imgBase64, string $fileName): void
    {
        // Store the new image
        $imageData = $this->assetService->storeFromBase64('img/locations/' . session("world") . '/', $imgBase64, $fileName);

        // Validate that the dimensions match the map's dimensions
        $width = $imageData['width'] / 32;
        $height = $imageData['height'] / 32;

        if ($width != $map->x || $height != $map->y) {
            throw ValidationException::withMessages([
                'img' => 'The image dimensions do not match the map dimensions.',
            ]);
        }

        // Update the map's src
        $map->update([
            'src' => session("world") . "/$fileName",
        ]);
    }

    /**
     * Remove the specified map from storage.
     *
     * @param Map $map
     * @return void
     */
    public function destroy(Map $map): void
    {

        if($this->getItemsTeleportingToMap($map)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'message' => 'Nie możesz usunąć mapy, na którą prowadzą jakieś przedmioty teleportacyjne',
            ]);
        }

        if($this->getDialogNodesTeleportingToMap($map)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'message' => 'Nie możesz usunąć mapy, na którą prowadzą dialogi teleportacyjne',
            ]);
        }

        $map->doorsLeadingToMap()->delete();
        $map->doors()->delete();
        $map->delete();
    }

    /**
     * Create a copy of the specified map.
     *
     * @param Map $map
     * @return Map
     */
    public function copy(Map $map): Map
    {
        // Create a new map with the same properties
        $newMap = $this->mapModel->create([
            'name' => $map->name . ' (kopia)',
            'src' => $map->src,
            'x' => $map->x,
            'y' => $map->y,
            'col' => $map->col,
            'battleground' => $map->battleground,
            'water' => $map->water,
            'pvp' => $map->pvp,
        ]);

        // Copy respawn point if exists
        if ($map->respawn_point_id) {
            $newMap->respawnPoint()->associate($map->respawn_point_id);
            $newMap->save();
        }

        return $newMap;
    }
}
