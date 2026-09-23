<?php

namespace App\Services\Mcp;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class VirtigiaMcpAuthService
{
    public function authenticate(string $accessToken): ?User
    {
        $baseUrl = rtrim((string) (config('services.virtigia_page.url') ?: config('services.laravelpassport.host')), '/');
        $profilePath = (string) config('services.virtigia_page.mcp_profile_path', '/api/mcp/profile');

        if ($baseUrl === '' || $accessToken === '') {
            return null;
        }

        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->timeout(10)
            ->get($baseUrl.'/'.ltrim($profilePath, '/'));

        if (! $response->successful() || data_get($response->json(), 'map_editor_access') !== true) {
            return null;
        }

        $payload = $response->json();
        $userId = data_get($payload, 'id');

        if (! is_numeric($userId)) {
            return null;
        }

        return User::query()->updateOrCreate(
            ['id' => (int) $userId],
            [
                'login' => (string) data_get($payload, 'login', 'virtigia-user-'.$userId),
                'name' => (string) data_get($payload, 'name', data_get($payload, 'login', 'Virtigia user')),
                'email' => data_get($payload, 'email'),
                'forum_background_src' => data_get($payload, 'forum_background_src'),
                'src' => (string) data_get($payload, 'src', ''),
                'roles' => data_get($payload, 'roles', []),
                'permissions' => data_get($payload, 'permissions', []),
            ],
        );
    }
}
