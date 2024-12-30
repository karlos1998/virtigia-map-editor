<?php
namespace App\Services;

use App\Http\Resources\ActivityLogResource;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
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
        return $this->fetchData(ActivityLogResource::class, $this->activityModel);
    }
}
