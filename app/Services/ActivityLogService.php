<?php
namespace App\Services;

use App\Http\Resources\ActivityLogResource;
use App\Models\User;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;
use Spatie\Activitylog\Models\Activity;

final class ActivityLogService extends BaseService
{
    public function __construct(private readonly Activity $activityModel)
    {
    }

    /**
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $causers = User::all()->map(function (User $user) {
            return new TableDropdownOption($user->name, fn($query) => $query->where('causer_id', $user->id));
        });

        return $this->fetchData(
            ActivityLogResource::class,
            $this->activityModel,
            new TableService(
                columns: [
                    'event' => new TableTextColumn(),
                    'causer_name' => new TableDropdownColumn(
                        placeholder: 'Wybierz gracza',
                        options: $causers->toArray(),
                    )
                ],
                globalFilterColumns: ['properties']
            )
        );
    }
}
