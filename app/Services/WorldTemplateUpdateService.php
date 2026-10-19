<?php

namespace App\Services;

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
            $template = WorldTemplate::query()->lockForUpdate()->findOrFail($template->id);
            $base = $template->baseTemplate;
            if (! $template->is_production || ! $template->is_active || $base === null || $base->is_production || ! $base->is_active) {
                throw ValidationException::withMessages(['template' => 'Produkcja musi mieć aktywny, nieprodukcyjny template bazowy.']);
            }

            $active = $template->updates()->whereIn('status', ['queued', 'comparing', 'queued_apply', 'applying', 'verification_required'])->latest('id')->first();
            if ($active !== null) {
                return $active;
            }
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
            WorldTemplate::query()->lockForUpdate()->findOrFail($update->target_template_id);
            $update = WorldTemplateUpdate::query()->lockForUpdate()->findOrFail($update->id);
            if ($update->status !== 'ready' || $update->snapshot_pruned || $update->expires_at?->isPast()) {
                throw ValidationException::withMessages(['update' => 'Porównanie jest nieaktualne lub aktualizacja już trwa. Przygotuj nowy diff.']);
            }
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
}
