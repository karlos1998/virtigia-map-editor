<?php

namespace App\Services;

use App\Enums\BaseNpcCategory;
use App\Http\Resources\NpcResource;
use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\Npc;
use App\Models\NpcGroup;
use App\Models\NpcLocation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Karlos3098\LaravelPrimevueTableService\Enum\TagSeverity;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOptionTag;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

class NpcService extends BaseService
{
    public function __construct(private readonly Npc $npcModel) {}

    public function createAndAssignDialog(Npc $npc): Dialog
    {
        $npc->loadMissing(['base', 'locations.map']);

        $firstLocation = $npc->locations->sortBy('id')->first();
        $mapName = $firstLocation?->map?->name ?? 'Unknown Map';
        $dialogName = sprintf('%s - %s', $npc->base->name, $mapName);

        $dialog = Dialog::create([
            'name' => $dialogName,
        ]);

        $startNode = $dialog->nodes()->create([
            'type' => 'start',
            'position' => [
                'x' => 0,
                'y' => 0,
            ],
        ]);

        $specialNode = $dialog->nodes()->create([
            'position' => [
                'x' => 200,
                'y' => 100,
            ],
            'content' => 'Oto przykładowa kwestia dialogowa',
        ]);

        $specialNode->options()->create(['label' => 'Zakończ rozmowę']);

        $dialog
            ->edges()->make()
            ->sourceNode()->associate($startNode)
            ->targetNode()->associate($specialNode)
            ->save();

        $npc->dialog()->associate($dialog);
        $npc->save();

        return $dialog;
    }

    /**
     * @throws \Exception
     */
    public function getAll(?int $mapId = null, array $filters = []): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = $this->npcModel->with(['locations.map', 'dialog', 'base']);
        app(NpcFilterService::class)->apply($query, $filters);

        // Filter by map if provided
        if ($mapId) {
            $query->whereHas('locations', function ($q) use ($mapId) {
                $q->where('map_id', $mapId);
            });
        }

        return $this->fetchData(
            NpcResource::class,
            $query,
            new TableService(
                columns: [
                    'enabled' => new TableDropdownColumn(
                        placeholder: 'Status',
                        options: [
                            new TableDropdownOptionTag('Dostępny', fn ($q) => $q->whereEnabled(true), TagSeverity::SUCCESS),
                            new TableDropdownOptionTag('Wylączony', fn ($q) => $q->whereEnabled(false), TagSeverity::DANGER),
                        ]
                    ),
                    'locations' => new TableDropdownColumn(
                        placeholder: 'Lokalizacje',
                        options: [
                            new TableDropdownOption('Ma jedną lokalizację', fn ($q) => $q->has('locations', '=', 1)),
                            new TableDropdownOption('Ma wiele lokalizacji', fn ($q) => $q->has('locations', '>', 1)),
                        ]
                    ),
                    'dialog' => new TableDropdownColumn(
                        placeholder: 'Dialog',
                        options: [
                            new TableDropdownOption('Ma dialog', fn ($q) => $q->whereHas('dialog')),
                            new TableDropdownOption('Nie ma dialogu', fn ($q) => $q->whereDoesntHave('dialog')),
                        ]
                    ),
                ],
                globalFilterColumns: ['base.name', 'base.lvl', 'base.rank', 'base.category'],
            )
        );
    }

    public function store(array $data): void
    {
        /**
         * @var Npc $npc
         */
        $npc = Npc::make();
        $npc->base()->associate($data['npc']);
        $npc->save();

        $npc->locations()->create([
            'map_id' => $data['location']['mapId'],
            'x' => $data['location']['x'],
            'y' => $data['location']['y'],
        ])->save();
    }

    /** @param array{npc_ids: array<int>, operation: string, dialog_id?: int|null, assignment_mode?: string|null} $data */
    public function bulkUpdate(array $data): int
    {
        return $this->npcModel->getConnection()->transaction(function () use ($data): int {
            if (in_array($data['operation'], ['enable_aggression', 'disable_aggression'], true)) {
                $bases = BaseNpc::query()->whereIn('id', $this->npcModel->newQuery()
                    ->whereIn('id', $data['npc_ids'])->select('base_npc_id'))
                    ->orderBy('id')->lockForUpdate()->get();
                if ($bases->contains(fn (BaseNpc $base) => $base->category !== BaseNpcCategory::MOB)) {
                    throw ValidationException::withMessages(['npc_ids' => 'Agresywność można zmieniać tylko dla mobów. Zaznacz instancje z kategorią MOB.']);
                }
                $updated = 0;
                foreach ($bases as $base) {
                    $base->is_aggressive = $data['operation'] === 'enable_aggression';
                    if ($base->isDirty()) {
                        $base->save();
                        $updated++;
                    }
                }

                return $updated;
            }

            $npcs = $this->npcModel->newQuery()->whereIn('id', $data['npc_ids'])->orderBy('id')->lockForUpdate()->get();
            if ($data['operation'] === 'enable_auto_dialog' && $npcs->contains(fn (Npc $npc) => $npc->dialog_id === null)) {
                throw ValidationException::withMessages(['npc_ids' => 'Automatyczny dialog można włączyć tylko instancjom z przypisanym dialogiem.']);
            }
            $updated = 0;
            foreach ($npcs as $npc) {
                switch ($data['operation']) {
                    case 'assign_dialog':
                        if ($data['assignment_mode'] === 'empty' && $npc->dialog_id !== null) {
                            continue 2;
                        }
                        $npc->dialog()->associate($data['dialog_id']);
                        break;
                    case 'detach_dialog':
                        $npc->dialog()->dissociate();
                        $npc->auto_start_dialog = false;
                        break;
                    case 'enable':
                    case 'disable':
                        $npc->enabled = $data['operation'] === 'enable';
                        break;
                    case 'enable_auto_dialog':
                    case 'disable_auto_dialog':
                        $npc->auto_start_dialog = $data['operation'] === 'enable_auto_dialog';
                        break;
                }
                if ($npc->isDirty()) {
                    $npc->save();
                    $updated++;
                }
            }

            return $updated;
        });
    }

    public function destroyLocation(Npc $npc, NpcLocation $npcLocation): void
    {
        if (! $npcLocation->npc()->is($npc)) {
            throw ValidationException::withMessages([
                'message' => 'Ta lokalizacja nie jest powiązana z tym npc',
            ]);
        }

        if ($npc->locations()->count() > 1) {
            $npcLocation->delete();
        } else {
            $npc->delete();
        }
    }

    public function update(Npc $npc, array $validated): void
    {
        $npc->fill($validated);
        if (array_key_exists('dialog', $validated)) {
            $npc->dialog()->associate($validated['dialog']);
        }
        $npc->save();
    }

    public function updateLocation(Npc $npc, NpcLocation $npcLocation, mixed $validated): void
    {
        if (! $npcLocation->npc()->is($npc)) {
            throw ValidationException::withMessages([
                'message' => 'Ta lokalizacja nie jest powiązana z tym npc',
            ]);
        }

        $npcLocation->update($validated);
    }

    public function detachGroup(Npc $npc)
    {
        $group = $npc->group;

        if ($group?->npcs()->count() > 2) {
            $npc->manually_group_detached = true;
            $npc->group()->disassociate()->save();
        } else {
            // Mark all NPCs in the group as manually detached
            foreach ($group->npcs as $groupNpc) {
                $groupNpc->manually_group_detached = true;
                $groupNpc->save();
            }
            $group->delete();
        }
    }

    public function addToGroup(Npc $sourceNpc, Npc $targetNpc)
    {
        // If source NPC doesn't have a group, create one
        if (! $sourceNpc->group_id) {
            $group = NpcGroup::create();
            $sourceNpc->manually_group_detached = false; // Reset the detached flag
            $sourceNpc->group()->associate($group)->save();
        }

        // Add target NPC to the source NPC's group
        $targetNpc->manually_group_detached = false; // Reset the detached flag
        $targetNpc->group()->associate($sourceNpc->group_id)->save();
    }

    public function createGroup(Collection $npcs)
    {
        if ($npcs->count() < 2) {
            throw ValidationException::withMessages([
                'message' => 'Grupa musi zawierać co najmniej 2 NPC',
            ]);
        }

        // Create a new group
        $group = NpcGroup::create();

        // Add all NPCs to the group
        foreach ($npcs as $npc) {
            $npc->manually_group_detached = false; // Reset the detached flag
            $npc->group()->associate($group)->save();
        }
    }

    public function searchHero(string $search)
    {
        return NpcResource::collection(
            $this->npcModel
                ->with(['base', 'locations'])
                ->whereHas('base', function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->where('rank', 'HERO');
                })
                ->limit(25)
                ->get()
        );
    }

    public function storeLocation(Npc $npc, array $data): void
    {
        $npc->locations()->create([
            'map_id' => $data['map_id'],
            'x' => $data['x'],
            'y' => $data['y'],
        ])->save();
    }
}
