<?php

namespace App\Services;

use App\Http\Resources\ActivityLogResource;
use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;
use Spatie\Activitylog\Models\Activity;

final class DialogActivityLogService extends BaseService
{
    public function __construct(private readonly Activity $activityModel) {}

    public function getForDialog(Dialog $dialog): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $dialog->loadMissing(['nodes.options', 'edges']);

        $dialogId = $dialog->id;
        $nodeIds = $dialog->nodes->pluck('id');
        $optionIds = $dialog->nodes->flatMap(fn (DialogNode $node) => $node->options->pluck('id'));
        $edgeIds = $dialog->edges->pluck('id');

        $causers = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => new TableDropdownOption($user->name, fn (Builder $query) => $query->where('causer_id', $user->id)));

        $subjectTypes = [
            new TableDropdownOption('Węzeł dialogowy', fn (Builder $query) => $query->where('subject_type', DialogNode::class)),
            new TableDropdownOption('Opcja dialogowa', fn (Builder $query) => $query->where('subject_type', DialogNodeOption::class)),
            new TableDropdownOption('Połączenie', fn (Builder $query) => $query->where('subject_type', DialogEdge::class)),
        ];

        $events = [
            new TableDropdownOption('Utworzono', fn (Builder $query) => $query->where('event', 'created')),
            new TableDropdownOption('Zaktualizowano', fn (Builder $query) => $query->where('event', 'updated')),
            new TableDropdownOption('Usunięto', fn (Builder $query) => $query->where('event', 'deleted')),
        ];

        $query = $this->activityModel->newQuery()->with('causer');

        $query->where(function (Builder $builder) use ($dialogId, $nodeIds, $optionIds, $edgeIds) {
            if ($nodeIds->isNotEmpty()) {
                $builder->orWhere(function (Builder $query) use ($nodeIds) {
                    $query->where('subject_type', DialogNode::class)
                        ->whereIn('subject_id', $nodeIds);
                });
            }

            $builder->orWhere(function (Builder $query) use ($dialogId) {
                $query->where('subject_type', DialogNode::class)
                    ->where(function (Builder $nestedQuery) use ($dialogId) {
                        $nestedQuery->where('properties->attributes->source_dialog_id', $dialogId)
                            ->orWhere('properties->old->source_dialog_id', $dialogId);
                    });
            });

            if ($optionIds->isNotEmpty()) {
                $builder->orWhere(function (Builder $query) use ($optionIds) {
                    $query->where('subject_type', DialogNodeOption::class)
                        ->whereIn('subject_id', $optionIds);
                });
            }

            if ($nodeIds->isNotEmpty()) {
                $builder->orWhere(function (Builder $query) use ($nodeIds) {
                    $query->where('subject_type', DialogNodeOption::class)
                        ->where(function (Builder $nestedQuery) use ($nodeIds) {
                            $nestedQuery->whereIn('properties->attributes->node_id', $nodeIds)
                                ->orWhereIn('properties->old->node_id', $nodeIds);
                        });
                });
            }

            if ($edgeIds->isNotEmpty()) {
                $builder->orWhere(function (Builder $query) use ($edgeIds) {
                    $query->where('subject_type', DialogEdge::class)
                        ->whereIn('subject_id', $edgeIds);
                });
            }

            $builder->orWhere(function (Builder $query) use ($dialogId) {
                $query->where('subject_type', DialogEdge::class)
                    ->where(function (Builder $nestedQuery) use ($dialogId) {
                        $nestedQuery->where('properties->attributes->source_dialog_id', $dialogId)
                            ->orWhere('properties->old->source_dialog_id', $dialogId);
                    });
            });
        });

        return $this->fetchData(
            ActivityLogResource::class,
            $query,
            new TableService(
                propName: 'logs',
                columns: [
                    'event' => new TableDropdownColumn(
                        placeholder: 'Zdarzenie',
                        options: $events,
                        sortable: true,
                    ),
                    'subject_type' => new TableDropdownColumn(
                        placeholder: 'Element',
                        options: $subjectTypes,
                        sortable: true,
                    ),
                    'causer_name' => new TableDropdownColumn(
                        placeholder: 'Użytkownik',
                        options: $causers->toArray(),
                    ),
                    'created_at' => new TableTextColumn(sortable: true),
                ],
                globalFilterColumns: ['description', 'properties']
            )
        );
    }
}
