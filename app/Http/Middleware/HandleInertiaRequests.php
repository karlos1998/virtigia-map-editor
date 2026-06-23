<?php

namespace App\Http\Middleware;

use App\Facades\AssetUrl;
use App\Jobs\RecordQueueHeartbeatJob;
use App\Services\WorldTemplateConnectionResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    private const int QUEUE_HEARTBEAT_MAX_AGE_MINUTES = 20;

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => $request->user() ? [
                'user' => [
                    ...$request->user()->only('id', 'name', 'email', 'src', 'forum_background_src'),
                    'src' => AssetUrl::fromPath($request->user()->src),
                    'forum_background_src' => AssetUrl::fromPath($request->user()->forum_background_src),
                ],
                'roles' => $request->user()->roles,
                'permissions' => $request->user()->permissions,
                'is_administrator' => $request->user()->hasAdministratorRole(),
                'world' => session('world'),
                'world_templates' => fn (): array => app(WorldTemplateConnectionResolver::class)->visibleOptions(),
            ] : null,
            'flash' => [
                'newApiToken' => fn () => $request->session()->get('newApiToken'),
                'success' => fn () => $request->session()->get('success'),
            ],
            'queueHealth' => fn (): array => $this->queueHealth(),
        ]);
    }

    /**
     * @return array{is_stale: bool, last_ran_at: string|null, checked_at: string, threshold_minutes: int, read_error: bool}
     */
    private function queueHealth(): array
    {
        $checkedAt = now();
        $lastRanAt = null;
        $isStale = true;
        $readError = false;

        try {
            $cachedLastRanAt = Cache::store('redis')->get(RecordQueueHeartbeatJob::CACHE_KEY);

            if (is_string($cachedLastRanAt) && $cachedLastRanAt !== '') {
                $lastRanAt = $cachedLastRanAt;
                $isStale = CarbonImmutable::parse($cachedLastRanAt)
                    ->lt($checkedAt->copy()->subMinutes(self::QUEUE_HEARTBEAT_MAX_AGE_MINUTES));
            }
        } catch (Throwable) {
            $readError = true;
        }

        return [
            'is_stale' => $isStale,
            'last_ran_at' => $lastRanAt,
            'checked_at' => $checkedAt->toIso8601String(),
            'threshold_minutes' => self::QUEUE_HEARTBEAT_MAX_AGE_MINUTES,
            'read_error' => $readError,
        ];
    }
}
