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
use Illuminate\Support\Facades\File;
use ReflectionClass;
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

        $models = collect(File::allFiles(app_path('Models')))
            ->map(function ($file) {
                $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();

                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    if($reflection->isSubclassOf(\App\Models\DynamicModel::class)) {
                        return new TableDropdownOption($file->getFilenameWithoutExtension(), fn($query) => $query->where('subject_type', $class));
                    }
                }

                return null;
            })
            ->filter()->values();

        return $this->fetchData(
            ActivityLogResource::class,
            $this->activityModel,
            new TableService(
                columns: [
                    'event' => new TableTextColumn(),
                    'causer_name' => new TableDropdownColumn(
                        placeholder: 'Wybierz gracza',
                        options: $causers->toArray(),
                    ),
                    'subject_type' => new TableDropdownColumn(
                        placeholder: 'Wybierz typ',
                        options: $models->toArray(),
                    ),
                ],
                globalFilterColumns: ['properties']
            )
        );
    }
}
