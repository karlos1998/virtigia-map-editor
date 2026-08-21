<?php

namespace App\Services;

use App\Models\MapTrack;
use App\Models\MapTrackCheckpoint;
use Illuminate\Support\Facades\DB;

class MapTrackService
{
    public function createCheckpoint(MapTrack $track, array $data): MapTrackCheckpoint
    {
        return DB::connection($track->getConnectionName())->transaction(function () use ($track, $data): MapTrackCheckpoint {
            $checkpoint = $track->checkpoints()->create([
                'map_id' => $data['map_id'],
                'name' => $data['name'] ?? null,
                'sequence' => ((int) $track->checkpoints()->max('sequence')) + 1,
            ]);

            $this->replaceTiles($checkpoint, $data['tiles']);

            return $checkpoint;
        });
    }

    public function updateCheckpoint(MapTrackCheckpoint $checkpoint, array $data): void
    {
        DB::connection($checkpoint->getConnectionName())->transaction(function () use ($checkpoint, $data): void {
            $checkpoint->update([
                'map_id' => $data['map_id'],
                'name' => $data['name'] ?? null,
            ]);
            $this->replaceTiles($checkpoint, $data['tiles']);
        });
    }

    public function deleteCheckpoint(MapTrack $track, MapTrackCheckpoint $checkpoint): void
    {
        DB::connection($track->getConnectionName())->transaction(function () use ($track, $checkpoint): void {
            $checkpoint->delete();
            $this->normalizeSequences($track);
        });
    }

    public function moveCheckpoint(MapTrack $track, MapTrackCheckpoint $checkpoint, string $direction): void
    {
        $targetSequence = $checkpoint->sequence + ($direction === 'up' ? -1 : 1);
        $target = $track->checkpoints()->where('sequence', $targetSequence)->first();

        if (! $target) {
            return;
        }

        DB::connection($track->getConnectionName())->transaction(function () use ($checkpoint, $target, $targetSequence): void {
            $originalSequence = $checkpoint->sequence;
            $checkpoint->update(['sequence' => 0]);
            $target->update(['sequence' => $originalSequence]);
            $checkpoint->update(['sequence' => $targetSequence]);
        });
    }

    private function replaceTiles(MapTrackCheckpoint $checkpoint, array $tiles): void
    {
        $checkpoint->tiles()->delete();

        $relation = $checkpoint->tiles();
        collect($tiles)
            ->map(fn (array $tile): array => [
                'map_track_checkpoint_id' => $checkpoint->id,
                'x' => $tile['x'],
                'y' => $tile['y'],
            ])
            ->chunk(500)
            ->each(fn ($chunk) => $relation->getRelated()->newQuery()->insert($chunk->all()));
    }

    private function normalizeSequences(MapTrack $track): void
    {
        $track->checkpoints()->get()->each(
            fn (MapTrackCheckpoint $checkpoint, int $index) => $checkpoint->update(['sequence' => $index + 1])
        );
    }
}
