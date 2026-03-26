<?php

namespace App\Http\Controllers;

use App\Services\BatchesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class BatchesController extends Controller
{
    public function __construct(protected BatchesService $batchesService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'status',
            'date_from',
            'date_to',
            'progress_min',
            'progress_max',
            'sort_by',
            'sort_direction',
        ]);
        $perPage = $request->get('per_page', 15);
        $batches = $this->batchesService->getBatches($filters, $perPage);
        $world = Auth::getSession()->get('world', 'retro');

        return Inertia::render('Batches/Index', [
            'batches' => $batches,
            'stats' => $this->batchesService->getBatchStats(),
            'filters' => $filters,
            'world' => $world,
            'syncStatuses' => [
                $this->resolveSyncStatus(
                    cacheKey: "base_item_usage_view_{$world}_batch_status",
                    label: 'Base item usage view',
                    description: 'Synchronizacja widoku użycia bazowych przedmiotów'
                ),
                $this->resolveSyncStatus(
                    cacheKey: "quest_step_guide_view_{$world}_batch_status",
                    label: 'Quest step guide view',
                    description: 'Synchronizacja widoku przewodników kroków questów'
                ),
            ],
        ]);
    }

    /**
     * @return array{
     *     label: string,
     *     description: string,
     *     status: string,
     *     started_at: ?string,
     *     updated_at: ?string,
     *     finished_at: ?string,
     *     last_synced_at: ?string,
     *     processed_chunks: int,
     *     chunks: int
     * }
     */
    private function resolveSyncStatus(string $cacheKey, string $label, string $description): array
    {
        $status = json_decode(Cache::store('redis')->get($cacheKey) ?? '{}', true);

        return [
            'label' => $label,
            'description' => $description,
            'status' => $status['status'] ?? 'never',
            'started_at' => $status['started_at'] ?? null,
            'updated_at' => $status['updated_at'] ?? null,
            'finished_at' => $status['finished_at'] ?? null,
            'last_synced_at' => $status['finished_at'] ?? $status['updated_at'] ?? $status['started_at'] ?? null,
            'processed_chunks' => (int) ($status['processed_chunks'] ?? 0),
            'chunks' => (int) ($status['chunks'] ?? 0),
        ];
    }
}
