<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Http\Resources\NpcResource;
use App\Models\BaseNpc;
use App\Models\Map;
use App\Models\Npc;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

class NpcService extends BaseService
{
    public function __construct(private readonly Npc $npcModel)
    {
    }

    public function getAll()
    {
        return $this->fetchData(
            NpcResource::class,
            $this->npcModel->with(['locations']),
            new TableService(
//                globalFilterColumns: ['base.name'], //todo - brak szukania po relacji ;c
            )
        );
    }

    public function store(array $data)
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
}
