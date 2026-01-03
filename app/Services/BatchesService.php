<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BatchesService
{
    public function getBatches(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DB::table('job_batches');

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $filters);

        // Paginate
        $batches = $query->paginate($perPage);

        // Transform the data
        $batches->getCollection()->transform(function ($batch) {
            return $this->transformBatch($batch);
        });

        return $batches;
    }

    public function getBatchStats(): array
    {
        $stats = DB::table('job_batches')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN finished_at IS NOT NULL THEN 1 ELSE 0 END) as finished,
                SUM(CASE WHEN cancelled_at IS NOT NULL THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN finished_at IS NULL AND cancelled_at IS NULL THEN 1 ELSE 0 END) as running,
                SUM(CASE WHEN failed_jobs > 0 THEN 1 ELSE 0 END) as with_errors
            ')
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'finished' => $stats->finished ?? 0,
            'cancelled' => $stats->cancelled ?? 0,
            'running' => $stats->running ?? 0,
            'with_errors' => $stats->with_errors ?? 0,
        ];
    }

    protected function applyFilters(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        // Status filter
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'finished':
                    $query->whereNotNull('finished_at');
                    break;
                case 'cancelled':
                    $query->whereNotNull('cancelled_at');
                    break;
                case 'running':
                    $query->whereNull('finished_at')->whereNull('cancelled_at');
                    break;
                case 'with_errors':
                    $query->where('failed_jobs', '>', 0);
                    break;
            }
        }

        // Name search
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Date range
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        // Progress range - use subqueries to avoid HAVING issues
        if (isset($filters['progress_min']) && $filters['progress_min'] >= 0) {
            $minProgress = $filters['progress_min'];
            $query->whereRaw('((total_jobs - pending_jobs - failed_jobs) / NULLIF(total_jobs, 0)) * 100 >= ?', [$minProgress]);
        }

        if (isset($filters['progress_max']) && $filters['progress_max'] <= 100) {
            $maxProgress = $filters['progress_max'];
            $query->whereRaw('((total_jobs - pending_jobs - failed_jobs) / NULLIF(total_jobs, 0)) * 100 <= ?', [$maxProgress]);
        }
    }

    protected function applySorting(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        // Validate sort direction
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Validate sort field and apply sorting
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'status':
                // Custom sorting for status
                $query->orderByRaw("
                    CASE
                        WHEN finished_at IS NOT NULL THEN 1
                        WHEN cancelled_at IS NOT NULL THEN 2
                        ELSE 3
                    END
                " . ($sortDirection === 'asc' ? 'ASC' : 'DESC'));
                $query->orderBy('created_at', 'desc');
                break;
            case 'progress':
                $query->orderByRaw('((total_jobs - pending_jobs - failed_jobs) / NULLIF(total_jobs, 0)) * 100 ' . $sortDirection);
                break;
            case 'total_jobs':
                $query->orderBy('total_jobs', $sortDirection);
                break;
            case 'finished_at':
                $query->orderBy('finished_at', $sortDirection);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                break;
        }
    }

    protected function transformBatch($batch): array
    {
        // Calculate progress percentage
        $totalJobs = $batch->total_jobs;
        $pendingJobs = $batch->pending_jobs;
        $failedJobs = $batch->failed_jobs;
        $completedJobs = $totalJobs - $pendingJobs - $failedJobs;

        $progress = $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 1) : 0;

        // Determine status
        $status = 'unknown';
        if ($batch->finished_at) {
            $status = 'finished';
        } elseif ($batch->cancelled_at) {
            $status = 'cancelled';
        } elseif ($batch->created_at && !$batch->finished_at) {
            $status = 'running';
        }

        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'total_jobs' => $totalJobs,
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'completed_jobs' => $completedJobs,
            'progress' => $progress,
            'status' => $status,
            'created_at' => $batch->created_at,
            'finished_at' => $batch->finished_at,
            'cancelled_at' => $batch->cancelled_at,
            'options' => json_decode($batch->options ?? '{}', true),
            'has_errors' => $failedJobs > 0,
        ];
    }
}
