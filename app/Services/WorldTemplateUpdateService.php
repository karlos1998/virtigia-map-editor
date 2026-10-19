<?php

namespace App\Services;

use App\Jobs\ProcessWorldTemplateMigrations;
use App\Jobs\ProcessWorldTemplateUpdate;
use App\Models\WorldTemplate;
use App\Models\WorldTemplateUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class WorldTemplateUpdateService
{
    public function start(WorldTemplate $template, int $userId): WorldTemplateUpdate
    {
        return DB::transaction(function () use ($template, $userId): WorldTemplateUpdate {
            $template = $this->lockTemplatePair($template);
            $base = $template->baseTemplate;
            if (! $template->is_production || ! $template->is_active || $base === null || $base->is_production || ! $base->is_active) {
                throw ValidationException::withMessages(['template' => 'Produkcja musi mieć aktywny, nieprodukcyjny template bazowy.']);
            }

            $active = $template->updates()->whereIn('status', WorldTemplateUpdate::ACTIVE_STATUSES)->latest('id')->first();
            if ($active !== null) {
                return $active;
            }
            $this->ensureNoMigration($base->id);
            $template->updates()->where('status', 'ready')->update(['status' => 'superseded', 'message' => 'Zastąpiono nowym porównaniem.']);
            $update = WorldTemplateUpdate::query()->create([
                'target_template_id' => $template->id, 'source_template_id' => $base->id,
                'requested_by' => $userId, 'status' => 'queued', 'message' => 'Oczekiwanie na porównanie w kolejce.',
            ]);
            ProcessWorldTemplateUpdate::dispatch($update->id)->afterCommit();

            return $update;
        });
    }

    public function approve(WorldTemplateUpdate $update): WorldTemplateUpdate
    {
        return DB::transaction(function () use ($update): WorldTemplateUpdate {
            $this->lockTemplatePair(WorldTemplate::query()->findOrFail($update->target_template_id));
            $update = WorldTemplateUpdate::query()->lockForUpdate()->findOrFail($update->id);
            if ($update->status !== 'ready' || $update->snapshot_pruned || $update->expires_at?->isPast()) {
                throw ValidationException::withMessages(['update' => 'Porównanie jest nieaktualne lub aktualizacja już trwa. Przygotuj nowy diff.']);
            }
            $this->ensureNoMigration($update->source_template_id);
            $update->update(['status' => 'queued_apply', 'progress' => 0, 'message' => 'Zatwierdzono. Oczekiwanie na zapis w kolejce.']);
            ProcessWorldTemplateUpdate::dispatch($update->id, true)->afterCommit();

            return $update;
        });
    }

    public function failed(int $updateId, Throwable $error): void
    {
        report($error);
        $update = WorldTemplateUpdate::query()->find($updateId);
        if ($update !== null && $update->status === 'applying') {
            $this->recover($update, $error->getMessage());

            return;
        }
        WorldTemplateUpdate::query()->whereKey($updateId)->whereNotIn('status', ['completed', 'failed'])
            ->update(['status' => 'failed', 'message' => mb_substr($error->getMessage(), 0, 255)]);
    }

    public function recover(WorldTemplateUpdate $update, string $message = 'Przerwano operację. Przygotuj nowe porównanie.'): void
    {
        if ($update->status === 'migrating') {
            app(WorldTemplateMigrationService::class)->markFailed($update, new \RuntimeException('Przerwano wykonanie migracji. Sprawdź ostatnią migrację przed ponownym uruchomieniem.'));

            return;
        }
        if (in_array($update->status, ['applying', 'verification_required'], true)) {
            try {
                $snapshots = app(WorldTemplateSnapshotService::class);
                [, $target] = $snapshots->connections($update);
                $matches = true;
                foreach ($update->manifest ?? [] as $table => $metadata) {
                    if ($snapshots->digest($target, $table, $metadata['keys']) !== $metadata['after_hash']) {
                        $matches = false;
                        break;
                    }
                }
                if ($matches && ! empty($update->manifest)) {
                    $update->update(['status' => 'completed', 'progress' => 100, 'message' => 'Potwierdzono zakończenie zapisu produkcji.', 'completed_at' => now()]);

                    return;
                }
            } catch (Throwable $error) {
                report($error);
                $update->update(['status' => 'verification_required', 'message' => 'Brak połączenia podczas weryfikacji wyniku. Zapis mógł się zakończyć. Automatycznie sprawdzimy stan przed kolejną aktualizacją.']);

                return;
            }
        }
        $update->update(['status' => 'failed', 'message' => mb_substr($message, 0, 255)]);
    }

    public function migrate(WorldTemplate $template, int $userId): WorldTemplateUpdate
    {
        return DB::transaction(function () use ($template, $userId): WorldTemplateUpdate {
            $template = $this->lockTemplatePair($template);
            $base = $template->baseTemplate;
            if (! $template->is_production || ! $template->is_active || $base === null || $base->is_production || ! $base->is_active) {
                throw ValidationException::withMessages(['template' => 'Migracje wymagają produkcji z aktywnym, nieprodukcyjnym template’em bazowym.']);
            }
            $active = WorldTemplateUpdate::query()->where('source_template_id', $base->id)
                ->whereIn('status', WorldTemplateUpdate::ACTIVE_STATUSES)->latest('id')->first();
            if ($active !== null) {
                if ($active->target_template_id === $template->id && in_array($active->status, ['queued_migration', 'migrating'], true)) {
                    return $active;
                }
                throw ValidationException::withMessages(['template' => 'Trwa operacja na jednej z tych baz. Poczekaj na jej zakończenie.']);
            }
            WorldTemplateUpdate::query()->where('source_template_id', $base->id)->where('status', 'ready')
                ->update(['status' => 'superseded', 'message' => 'Porównanie unieważniono przed uruchomieniem migracji.']);
            $update = WorldTemplateUpdate::query()->create([
                'target_template_id' => $template->id, 'source_template_id' => $base->id, 'requested_by' => $userId,
                'status' => 'queued_migration', 'migration_steps' => [], 'message' => 'Oczekiwanie na uruchomienie migracji.',
            ]);
            ProcessWorldTemplateMigrations::dispatch($update->id)->afterCommit();

            return $update;
        });
    }

    private function lockTemplatePair(WorldTemplate $template): WorldTemplate
    {
        $pair = WorldTemplate::query()->whereIn('id', array_filter([$template->id, $template->base_template_id]))
            ->orderBy('id')->lockForUpdate()->get();
        $locked = $pair->firstWhere('id', $template->id);
        abort_if($locked === null, 404);
        $locked->setRelation('baseTemplate', $pair->firstWhere('id', $locked->base_template_id));

        return $locked;
    }

    private function ensureNoMigration(int $sourceId): void
    {
        if (WorldTemplateUpdate::query()->where('source_template_id', $sourceId)->whereIn('status', ['queued_migration', 'migrating'])->exists()) {
            throw ValidationException::withMessages(['template' => 'Trwa migracja template’u bazowego. Poczekaj na jej zakończenie.']);
        }
    }
}
