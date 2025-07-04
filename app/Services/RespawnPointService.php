<?php

namespace App\Services;

use App\Http\Resources\RespawnPointResource;
use App\Models\RespawnPoint;
use Illuminate\Support\Facades\Cache;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;

class RespawnPointService
{
    public function getRespawnPointsSelectOptions()
    {
        $points = Cache::remember('respawn_points_data', 10, function () {
            return RespawnPoint::with('map')->get(['id', 'map_id'])->toArray();
        });

        return collect($points)->map(function($point) {
            $mapName = $point['map']['name'] ?? 'Unknown';
            $pointId = $point['id'];
            return new TableDropdownOption($mapName, fn($query) => $query->where('respawn_point_id', $pointId));
        })->toArray();
    }

    public function create(array $data): RespawnPoint
    {
        $respawnPoint = new RespawnPoint();
        $respawnPoint->map_id = $data['map_id'];
        $respawnPoint->x = $data['x'];
        $respawnPoint->y = $data['y'];
        $respawnPoint->max_steps = 1;
        $respawnPoint->save();

        // Clear cache
        Cache::forget('respawn_points_data');

        return $respawnPoint;
    }

    public function update(RespawnPoint $respawnPoint, array $data): RespawnPoint
    {
        $respawnPoint->map_id = $data['map_id'];
        $respawnPoint->x = $data['x'];
        $respawnPoint->y = $data['y'];
        $respawnPoint->save();

        // Clear cache
        Cache::forget('respawn_points_data');

        return $respawnPoint;
    }

    public function delete(RespawnPoint $respawnPoint): bool
    {
        $result = $respawnPoint->delete();

        // Clear cache
        Cache::forget('respawn_points_data');

        return $result;
    }
}
