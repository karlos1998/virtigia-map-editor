<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Http\Resources\NpcResource;
use App\Models\BaseNpc;
use App\Models\Map;
use App\Models\Npc;
use App\Models\NpcGroup;
use App\Models\NpcLocation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Karlos3098\LaravelPrimevueTableService\Enum\TagSeverity;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOptionTag;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;

class NpcService extends BaseService
{
    public function __construct(private readonly Npc $npcModel)
    {
    }

    /**
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            NpcResource::class,
            $this->npcModel->with(['locations', 'dialog', 'base']),
            new TableService(
                columns: [
                    'enabled' => new TableDropdownColumn(
                        placeholder: 'Status',
                        options: [
                            new TableDropdownOptionTag('Dostępny', fn($q) => $q->whereEnabled(true), TagSeverity::SUCCESS),
                            new TableDropdownOptionTag('Wylączony', fn($q) => $q->whereEnabled(false), TagSeverity::DANGER),
                        ]
                    ),
                    'locations' => new TableDropdownColumn(
                        placeholder: 'Lokalizacje',
                        options: [
                            new TableDropdownOption('Ma jedną lokalizację', fn($q) => $q->has('locations', '=', 1)),
                            new TableDropdownOption('Ma wiele lokalizacji', fn($q) => $q->has('locations', '>', 1)),
                        ]
                    ),
                    'dialog' => new TableDropdownColumn(
                        placeholder: 'Dialog',
                        options: [
                            new TableDropdownOption('Ma dialog', fn($q) => $q->whereHas('dialog')),
                            new TableDropdownOption('Nie ma dialogu', fn($q) => $q->whereDoesntHave('dialog')),
                        ]
                    )
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

    public function destroyLocation(Npc $npc, NpcLocation $npcLocation): void
    {
        if(!$npcLocation->npc()->is($npc))
        {
            throw ValidationException::withMessages([
                'message' => 'Ta lokalizacja nie jest powiązana z tym npc',
            ]);
        }

        if($npc->locations()->count() > 1)
        {
            $npcLocation->delete();
        }
        else
        {
            $npc->delete();
        }
    }

    public function update(Npc $npc, array $validated): void
    {
        $npc->fill($validated);
        $npc->dialog()->associate($validated['dialog']);
        $npc->save();
    }

    public function updateLocation(Npc $npc, NpcLocation $npcLocation, mixed $validated): void
    {
        if(!$npcLocation->npc()->is($npc))
        {
            throw ValidationException::withMessages([
                'message' => 'Ta lokalizacja nie jest powiązana z tym npc',
            ]);
        }

        $npcLocation->update($validated);
    }

    public function detachGroup(Npc $npc)
    {
        $group = $npc->group;

        if($group?->npcs()->count() > 2)
        {
            $npc->manually_group_detached = true;
            $npc->group()->disassociate()->save();
        }
        else
        {
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
        if (!$sourceNpc->group_id) {
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
                ->whereHas('base', function($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
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
