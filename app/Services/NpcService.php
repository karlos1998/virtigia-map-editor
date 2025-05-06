<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Http\Resources\NpcResource;
use App\Models\BaseNpc;
use App\Models\Map;
use App\Models\Npc;
use App\Models\NpcLocation;
use Illuminate\Validation\ValidationException;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

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
            $npc->group()->disassociate()->save();
        }
        else
        {
            $group->delete();
        }
    }
}
