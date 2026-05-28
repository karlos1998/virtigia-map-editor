<?php

namespace App\Services;

class OAuthPermissionPayload
{
    private const MAP_EDITOR_ACCESS = 'map-editor-access';

    public function hasMapEditorAccess(array $payload): bool
    {
        if (array_key_exists('map_editor_access', $payload)) {
            return filter_var($payload['map_editor_access'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->hasGlobalPermission($payload, self::MAP_EDITOR_ACCESS);
    }

    private function hasGlobalPermission(array $payload, string $permissionName): bool
    {
        foreach ($payload['permissions'] ?? [] as $permission) {
            if (is_string($permission)) {
                if ($permission === $permissionName) {
                    return true;
                }

                continue;
            }

            $permission = is_object($permission) ? (array) $permission : $permission;
            if (! is_array($permission)) {
                continue;
            }

            $name = $permission['name'] ?? null;
            $worldName = $permission['world_name'] ?? data_get($permission, 'pivot.world_name');

            if ($name === $permissionName && ($worldName === null || $worldName === '')) {
                return true;
            }
        }

        return false;
    }
}
