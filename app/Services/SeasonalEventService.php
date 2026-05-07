<?php

namespace App\Services;

use App\Models\SeasonalEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class SeasonalEventService
{
    public function __construct(private SeasonalEvent $seasonalEventModel) {}

    public function getAllForList(): Collection
    {
        return $this->seasonalEventModel
            ->newQuery()
            ->orderBy('name')
            ->get()
            ->map(fn (SeasonalEvent $event) => $this->mapForOutput($event))
            ->values();
    }

    public function create(array $validated): SeasonalEvent
    {
        /** @var SeasonalEvent $event */
        $event = $this->seasonalEventModel->newQuery()->create([
            ...$validated,
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);

        return $event;
    }

    public function update(SeasonalEvent $seasonalEvent, array $validated): void
    {
        $seasonalEvent->update([
            ...$validated,
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);
    }

    public function delete(SeasonalEvent $seasonalEvent): void
    {
        $seasonalEvent->delete();
    }

    private function mapForOutput(SeasonalEvent $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'active' => $event->active,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'is_currently_active' => $event->isCurrentlyActive(),
        ];
    }
}

