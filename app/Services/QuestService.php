<?php

namespace App\Services;

use App\Http\Resources\QuestResource;
use App\Models\Quest;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class QuestService extends BaseService
{
    public function __construct(
        private readonly Quest $questModel,
    )
    {
    }

    public function findAll()
    {
        return $this->fetchData(
            QuestResource::class,
            $this->questModel,
            new TableService(
                globalFilterColumns: ['name']
            ),
        );
    }
}
