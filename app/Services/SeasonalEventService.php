<?php

namespace App\Services;

use App\Models\DialogNodeOption;
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

    public function getShowData(SeasonalEvent $seasonalEvent): array
    {
        $seasonalEvent->load(['baseNpcs.locations']);

        $baseNpcs = $seasonalEvent->baseNpcs
            ->map(fn ($baseNpc) => [
                'id' => $baseNpc->id,
                'name' => $baseNpc->name,
                'lvl' => $baseNpc->lvl,
                'rank' => $baseNpc->rank?->value,
                'locations_count' => $baseNpc->locations->count(),
                'npcs_preview' => $baseNpc->locations
                    ->take(5)
                    ->map(fn ($npc) => [
                        'id' => $npc->id,
                    ])
                    ->values(),
            ])
            ->sortBy('name')
            ->values();

        $dialogOptions = DialogNodeOption::query()
            ->whereNotNull('rules')
            ->with(['node.dialog.npcs.base'])
            ->get()
            ->filter(function (DialogNodeOption $option) use ($seasonalEvent) {
                return (int) data_get($option->rules, 'seasonalEvent.value') === (int) $seasonalEvent->id;
            })
            ->map(function (DialogNodeOption $option) {
                $dialog = $option->node?->dialog;
                $dialogNpcs = collect($dialog?->npcs ?? [])
                    ->map(fn ($npc) => [
                        'id' => $npc->id,
                        'base_npc_id' => $npc->base?->id,
                        'base_npc_name' => $npc->base?->name,
                    ])
                    ->values();

                return [
                    'option_id' => $option->id,
                    'option_label' => $option->label,
                    'node_id' => $option->node?->id,
                    'node_type' => $option->node?->type,
                    'dialog_id' => $dialog?->id,
                    'dialog_name' => $dialog?->name,
                    'dialog_npcs' => $dialogNpcs,
                ];
            })
            ->sortBy([
                ['dialog_name', 'asc'],
                ['option_id', 'asc'],
            ])
            ->values();

        return [
            'event' => $this->mapForOutput($seasonalEvent),
            'base_npcs' => $baseNpcs,
            'dialog_options' => $dialogOptions,
        ];
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
