<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWorldTemplateMigrations;
use App\Jobs\ProcessWorldTemplateUpdate;
use App\Models\WorldTemplateUpdate;
use App\Services\WorldTemplateUpdateService;
use Illuminate\Console\Command;

class PruneWorldTemplateUpdates extends Command
{
    protected $signature = 'world-template-updates:prune';

    protected $description = 'Reconcile interrupted updates and prune template snapshots older than seven days';

    public function handle(WorldTemplateUpdateService $updates): int
    {
        WorldTemplateUpdate::query()->whereIn('status', ['queued', 'queued_apply'])
            ->where('updated_at', '<', now()->subMinute())
            ->eachById(fn (WorldTemplateUpdate $update) => ProcessWorldTemplateUpdate::dispatch($update->id, $update->status === 'queued_apply'));
        WorldTemplateUpdate::query()->where('status', 'queued_migration')->where('updated_at', '<', now()->subMinute())
            ->eachById(fn (WorldTemplateUpdate $update) => ProcessWorldTemplateMigrations::dispatch($update->id));

        WorldTemplateUpdate::query()->where(function ($query): void {
            $query->where(fn ($query) => $query->whereIn('status', ['comparing', 'applying', 'migrating'])->where('updated_at', '<', now()->subMinutes(70)))
                ->orWhere(fn ($query) => $query->where('status', 'verification_required')->where('updated_at', '<', now()->subMinutes(5)));
        })
            ->eachById(fn (WorldTemplateUpdate $update) => $updates->recover($update));

        WorldTemplateUpdate::query()->where(function ($query): void {
            $query->where('snapshot_pruned', false)->orWhereExists(function ($query): void {
                $query->selectRaw('1')->from('world_template_update_rows')
                    ->whereColumn('world_template_update_id', 'world_template_updates.id');
            });
        })
            ->whereIn('status', ['ready', 'failed', 'completed', 'superseded'])
            ->where('created_at', '<', now()->subDays(7))
            ->eachById(function (WorldTemplateUpdate $update): void {
                if (! $update->snapshot_pruned) {
                    $claimed = WorldTemplateUpdate::query()->whereKey($update->id)
                        ->whereIn('status', ['ready', 'failed', 'completed', 'superseded'])
                        ->update(['snapshot_pruned' => true]);
                    if ($claimed === 0) {
                        return;
                    }
                }
                do {
                    $ids = $update->snapshotRows()->limit(500)->pluck('id');
                    $update->snapshotRows()->whereIn('id', $ids)->delete();
                } while ($ids->isNotEmpty());
            });

        return self::SUCCESS;
    }
}
