<?php

namespace App\Services\Mcp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use LogicException;

class RetroEngineAnalysisService
{
    /** @return array<string, mixed> */
    public function npc(int $baseNpcId): array
    {
        return $this->request()->get("/internal/map-editor/base-npcs/{$baseNpcId}")->throw()->json();
    }

    /** @return array<string, mixed> */
    public function equipment(int $level, string $profession, int $perCategory): array
    {
        return $this->request()->get('/internal/map-editor/equipment-candidates', [
            'level' => $level,
            'profession' => $profession,
            'perCategory' => $perCategory,
        ])->throw()->json();
    }

    /** @return array<string, mixed> */
    public function skills(int $level, string $profession): array
    {
        return $this->request()->get('/internal/map-editor/skills', [
            'level' => $level,
            'profession' => $profession,
        ])->throw()->json();
    }

    /** @param array<string, mixed> $build @return array<string, mixed> */
    public function simulate(array $build): array
    {
        return $this->request()->post('/internal/map-editor/combat/simulate', $build)->throw()->json();
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function loot(array $input): array
    {
        return $this->request()->post('/internal/map-editor/loot/analyze', $input)->throw()->json();
    }

    public function assertRetro(?string $world): void
    {
        if (strtolower(trim($world ?: 'retro')) !== 'retro') {
            throw ValidationException::withMessages([
                'world' => 'Analiza statystyk, walki i dropu jest obecnie dostępna wyłącznie dla świata Retro.',
            ]);
        }
    }

    private function request(): PendingRequest
    {
        $url = trim((string) config('services.virtigia_retro_engine.url'));
        $token = trim((string) config('services.virtigia_retro_engine.token'));

        if ($url === '' || $token === '') {
            throw new LogicException('Połączenie Map Editor → Retro Engine nie ma skonfigurowanych poświadczeń.');
        }

        return Http::baseUrl($url)
            ->withToken($token)
            ->acceptJson()
            ->timeout((int) config('services.virtigia_retro_engine.timeout', 60));
    }
}
