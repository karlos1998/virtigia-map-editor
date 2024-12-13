<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Http\Resources\NpcResource;
use App\Models\Map;
use App\Models\Npc;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

class NpcService extends BaseService
{
    private Npc $npcModel;
    public function __construct(Npc $npc)
    {
        $this->npcModel = $npc->setConnectionName('retro');
    }

    public function getAll()
    {
        return $this->fetchData(
            NpcResource::class,
            $this->npcModel->newQuery()
        );
    }
}
