<?php

namespace App\Services;

class BaseItemTeleportService
{
    public function teleportToHasMissingMapName(mixed $teleportTo): bool
    {
        if (! is_array($teleportTo) || count($teleportTo) < 3) {
            return false;
        }

        if (! array_key_exists(3, $teleportTo)) {
            return true;
        }

        $mapName = $teleportTo[3];

        if ($mapName === null) {
            return true;
        }

        if (! is_string($mapName)) {
            return false;
        }

        return in_array(strtolower(trim($mapName)), ['', 'undefined', 'null'], true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $mapNamesById
     * @return array<string, mixed>
     */
    public function fillMissingTeleportMapName(array $attributes, array $mapNamesById): array
    {
        $teleportTo = $attributes['teleportTo'] ?? null;

        if (! $this->teleportToHasMissingMapName($teleportTo)) {
            return $attributes;
        }

        $mapId = $teleportTo[0] ?? null;

        if (! is_numeric($mapId)) {
            return $attributes;
        }

        $mapName = $mapNamesById[(int) $mapId] ?? null;

        if (! is_string($mapName) || trim($mapName) === '') {
            return $attributes;
        }

        $teleportTo[3] = $mapName;
        $attributes['teleportTo'] = $teleportTo;

        return $attributes;
    }
}
