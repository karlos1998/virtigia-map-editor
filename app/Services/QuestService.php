<?php

namespace App\Services;

use App\Http\Resources\QuestListResource;
use App\Models\Quest;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
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
            QuestListResource::class,
            $this->questModel,
            new TableService(
                columns: [
                    'is_daily' => new TableDropdownColumn(
                        placeholder: 'Dzienny',
                        options: [
                            new TableDropdownOption('Tak', fn($query) => $query->whereHas('steps', fn($q) => $q->where('auto_advance_next_day', true))),
                            new TableDropdownOption('Nie', fn($query) => $query->whereDoesntHave('steps', fn($q) => $q->where('auto_advance_next_day', true))),
                        ]
                    ),
                ],
                globalFilterColumns: ['name']
            ),
        );
    }
}
