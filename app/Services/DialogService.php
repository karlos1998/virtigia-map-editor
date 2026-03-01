<?php

namespace App\Services;

use App\Http\Resources\DialogResource;
use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;
use Spatie\Activitylog\Models\Activity;

class DialogService extends BaseService
{
    public function __construct(
        private readonly Dialog $dialogModel,
        private readonly Activity $activityModel,
    ) {}

    public function getAll(): AnonymousResourceCollection
    {
        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                return new TableDropdownOption($user->name, fn () => null);
            });

        $tableService = new TableService(
            propName: 'dialogs',
            columns: [
                'id' => new TableTextColumn(sortable: true),
                'name' => new TableTextColumn(sortable: true),
                'last_activity_at' => new TableTextColumn(
                    placeholder: 'Ostatnia edycja',
                    sortable: true,
                ),
                'last_editor_id' => new TableDropdownColumn(
                    placeholder: 'Ostatni edytujący',
                    options: $users->toArray(),
                ),
            ],
            globalFilterColumns: ['name'],
        );

        $filters = $this->normalizeTableFilters(request('tables.dialogs.filters'));
        $tableService->setActiveFilters($filters);

        $globalFilter = request('tables.dialogs.globalFilter');
        if (is_string($globalFilter) && $globalFilter !== '') {
            $tableService->setGlobalFilter($globalFilter);
        }

        $sortField = request('tables.dialogs.sortField');
        $sortOrder = request('tables.dialogs.sortOrder') === 'DESC' ? 'DESC' : 'ASC';
        if (is_string($sortField) && $tableService->hasColumnExist($sortField)) {
            $tableService->setSortData($sortField, $sortOrder);
        } else {
            $tableService->setSortData('last_activity_at', 'DESC');
        }

        $dialogs = $this->dialogModel->query()
            ->with(['npcs', 'npcs.locations'])
            ->withCount('npcs')
            ->get();

        $dialogs = $this->appendLastActivityMetadata($dialogs);
        $dialogs = $this->applyDialogListFilters($dialogs, $filters, $globalFilter);
        $dialogs = $this->applyDialogListSorting($dialogs, $tableService->sortField, $tableService->sortOrder);

        $perPage = $tableService->getRowsPerPage();
        $page = max(1, (int) request($tableService->getPageParameterName(), 1));

        $paginator = new LengthAwarePaginator(
            items: $dialogs->forPage($page, $perPage)->values(),
            total: $dialogs->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => $tableService->getPageParameterName(),
            ],
        );

        $resource = DialogResource::collection($paginator);
        $resource->additional(['tableData' => $tableService]);

        return $resource;
    }

    private function appendLastActivityMetadata(Collection $dialogs): Collection
    {
        if ($dialogs->isEmpty()) {
            return $dialogs;
        }

        $dialogIds = $dialogs->pluck('id');
        $dialogIdLookup = $dialogIds->flip();

        $nodes = DialogNode::query()
            ->select(['id', 'source_dialog_id'])
            ->whereIn('source_dialog_id', $dialogIds)
            ->get();

        $nodeToDialog = $nodes->pluck('source_dialog_id', 'id');

        $options = DialogNodeOption::query()
            ->select(['id', 'node_id'])
            ->whereIn('node_id', $nodes->pluck('id'))
            ->get();

        $optionToDialog = $options
            ->mapWithKeys(function (DialogNodeOption $option) use ($nodeToDialog) {
                return [$option->id => $nodeToDialog->get($option->node_id)];
            })
            ->filter();

        $edgeToDialog = DialogEdge::query()
            ->select(['id', 'source_dialog_id'])
            ->whereIn('source_dialog_id', $dialogIds)
            ->get()
            ->pluck('source_dialog_id', 'id');

        $activities = $this->activityModel->newQuery()
            ->with('causer:id,name')
            ->whereIn('subject_type', [
                DialogNode::class,
                DialogNodeOption::class,
                DialogEdge::class,
            ])
            ->orderByDesc('created_at')
            ->get();

        $latestActivityByDialogId = [];

        foreach ($activities as $activity) {
            $dialogId = match ($activity->subject_type) {
                DialogNode::class => $nodeToDialog->get($activity->subject_id)
                    ?? data_get($activity->properties, 'attributes.source_dialog_id')
                    ?? data_get($activity->properties, 'old.source_dialog_id'),
                DialogNodeOption::class => $optionToDialog->get($activity->subject_id)
                    ?? $nodeToDialog->get((int) (data_get($activity->properties, 'attributes.node_id') ?? 0))
                    ?? $nodeToDialog->get((int) (data_get($activity->properties, 'old.node_id') ?? 0)),
                DialogEdge::class => $edgeToDialog->get($activity->subject_id)
                    ?? data_get($activity->properties, 'attributes.source_dialog_id')
                    ?? data_get($activity->properties, 'old.source_dialog_id'),
                default => null,
            };

            if ($dialogId === null) {
                continue;
            }

            $dialogId = (int) $dialogId;

            if (! $dialogIdLookup->has($dialogId) || array_key_exists($dialogId, $latestActivityByDialogId)) {
                continue;
            }

            $latestActivityByDialogId[$dialogId] = $activity;
        }

        return $dialogs->map(function (Dialog $dialog) use ($latestActivityByDialogId) {
            $lastActivity = $latestActivityByDialogId[$dialog->id] ?? null;

            $dialog->setAttribute('last_activity_at', $lastActivity?->created_at?->toISOString());
            $dialog->setAttribute('last_editor_id', $lastActivity?->causer_id ? (int) $lastActivity->causer_id : null);
            $dialog->setAttribute('last_editor_name', $lastActivity?->causer?->name);

            return $dialog;
        });
    }

    private function applyDialogListFilters(Collection $dialogs, \stdClass $filters, ?string $globalFilter): Collection
    {
        if (is_string($globalFilter) && trim($globalFilter) !== '') {
            $searchTerms = preg_split('/\s+/', trim($globalFilter), -1, PREG_SPLIT_NO_EMPTY);

            $dialogs = $dialogs->filter(function (Dialog $dialog) use ($searchTerms) {
                $name = mb_strtolower($dialog->name);

                foreach ($searchTerms as $term) {
                    if (! str_contains($name, mb_strtolower($term))) {
                        return false;
                    }
                }

                return true;
            });
        }

        $selectedLastEditor = data_get($filters, 'last_editor_id.constraints.0.value.label');
        if (is_string($selectedLastEditor) && $selectedLastEditor !== '') {
            $dialogs = $dialogs->filter(function (Dialog $dialog) use ($selectedLastEditor) {
                return $dialog->last_editor_name === $selectedLastEditor;
            });
        }

        $selectedLastActivityDate = data_get($filters, 'last_activity_at.constraints.0.value');
        if (is_string($selectedLastActivityDate) && $selectedLastActivityDate !== '') {
            $selectedDate = Carbon::parse($selectedLastActivityDate)->toDateString();

            $dialogs = $dialogs->filter(function (Dialog $dialog) use ($selectedDate) {
                if (! $dialog->last_activity_at) {
                    return false;
                }

                return Carbon::parse($dialog->last_activity_at)->toDateString() === $selectedDate;
            });
        }

        return $dialogs->values();
    }

    private function applyDialogListSorting(Collection $dialogs, string $sortField, string $sortOrder): Collection
    {
        $sorted = match ($sortField) {
            'name' => $dialogs->sortBy(fn (Dialog $dialog) => mb_strtolower($dialog->name)),
            'last_activity_at' => $dialogs->sortBy(fn (Dialog $dialog) => $dialog->last_activity_at ?? ''),
            default => $dialogs->sortBy(fn (Dialog $dialog) => $dialog->id),
        };

        if ($sortOrder === 'DESC') {
            $sorted = $sorted->reverse();
        }

        return $sorted->values();
    }

    private function normalizeTableFilters(mixed $filtersInput): \stdClass
    {
        if ($filtersInput instanceof \stdClass) {
            return $filtersInput;
        }

        if (is_string($filtersInput) && $filtersInput !== '') {
            $decoded = json_decode($filtersInput);

            return $decoded instanceof \stdClass ? $decoded : new \stdClass;
        }

        if (is_array($filtersInput)) {
            $decoded = json_decode(json_encode($filtersInput));

            return $decoded instanceof \stdClass ? $decoded : new \stdClass;
        }

        return new \stdClass;
    }

    public function addNode(Dialog $dialog, array $data)
    {
        $node = $dialog->nodes()->create($data);

        if (($data['type'] ?? 'special') === 'special') {
            $node->options()->create([
                'label' => 'Zakończ.',
            ]);
        }

        return $node->fresh();
    }

    public function moveNode(DialogNode $dialogNode, array $data)
    {
        $dialogNode->update($data);
    }

    public function addEdge(Dialog $dialog, array $data)
    {

        $targetNode = $dialog->nodes()->find($data['targetNodeId']);

        $edge = $dialog
            ->edges()->make()
            ->targetNode()->associate($targetNode);

        if (! $data['sourceNodeIsInput']) {
            /**
             * @var DialogNode $sourceNode
             */
            $sourceNode = $dialog->nodes()->find($data['sourceNodeId']);
            if (! $sourceNode) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'message' => 'Nie znaleziono źródłowego węzła.',
                ]);
            }

            /**
             * @var DialogNodeOption $sourceOption
             */
            $sourceOption = $sourceNode->options()->find($data['sourceOptionId']);
            if (! $sourceOption) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'message' => 'Nie znaleziono źródłowej opcji połączenia.',
                ]);
            }

            //            if ($sourceOption->edges()->count() > 1)
            //            {
            //                throw \Illuminate\Validation\ValidationException::withMessages([
            //                    'message' => 'Opcja może mieć NA TEN MOMENT tylko 2 połączenia',
            //                ]);
            //            }

            $edge->sourceOption()->associate($sourceOption);
        } else {
            /**
             * @var DialogNode $sourceNode
             */
            $sourceNode = $dialog->nodes()->find($data['sourceNodeId']);
            if (! $sourceNode || ! in_array($sourceNode->type, ['start', 'randomizer'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'message' => 'Wybrane źródło połączenia nie obsługuje bezpośrednich wyjść.',
                ]);
            }

            $edge->sourceNode()->associate($sourceNode);
            //            if ($dialog->edges()->whereNull('source_option_id')->count() >= 1)
            //            {
            //                throw \Illuminate\Validation\ValidationException::withMessages([
            //                    'message' => 'Node wejściowy może mieć NA TEN MOMENT tylko jedno połączenie',
            //                ]);
            //            }

            //            if($targetNode->type != 'special')
            //            {
            //                throw \Illuminate\Validation\ValidationException::withMessages([
            //                    'message' => 'Node wejściowy może być połączony tylko z normalnymi dialogami (nie sklepami czy teleportacją)',
            //                ]);
            //            }
        }

        $edge->save();

        return $edge->fresh();
    }

    public function addOption(DialogNode $dialogNode)
    {
        $maxOrder = $dialogNode->options()->max('order');
        $newOrder = is_null($maxOrder) ? 0 : $maxOrder + 1;

        return $dialogNode->options()->create([
            'label' => 'Treść odpowiedzi',
            'order' => $newOrder,
        ]);
    }

    public function updateAction(Dialog $dialog, DialogNode $dialogNode, array $data): DialogNode
    {
        $dialogNode->update([
            'action_data' => $data,
        ]);

        return $dialogNode;
    }

    public function store(array $validated)
    {
        $dialog = Dialog::create([
            'name' => $validated['name'],
        ]);

        $defaultNode = $dialog->nodes()->create([
            'type' => 'start',
            'position' => [
                'x' => 0,
                'y' => 0,
            ],
        ]);

        $node = $dialog->nodes()->create([
            'position' => [
                'x' => 200,
                'y' => 100,
            ],
            'content' => 'Oto przykładowa kwestia dialogowa',
        ]);
        $node->options()->create(['label' => 'Zakończ rozmowę']);

        $dialog
            ->edges()->make()
            ->sourceNode()->associate($defaultNode)
            ->targetNode()->associate($node)
            ->save();

        return $dialog;
    }

    public function update(Dialog $dialog, array $validated)
    {
        $dialog->update([
            'name' => $validated['name'],
        ]);

        return $dialog;
    }

    public function updateNode(Dialog $dialog, DialogNode $dialogNode, array $validated)
    {
        $dialogNode->update($validated);
    }

    public function updateOption(Dialog $dialog, DialogNode $dialogNode, DialogNodeOption $dialogNodeOption, mixed $validated): DialogNodeOption
    {
        $dialogNodeOption->update($validated);

        $edgesData = $validated['edges'];
        //        dump($edgesData, $dialogNodeOption->edges->pluck('id'));
        foreach ($edgesData as $edgeData) {
            $foundEdge = $dialogNodeOption->edges->where('id', $edgeData['edge_id'])->first();
            if (! $foundEdge) {
                continue;
            }

            $foundEdge->update(['rules' => $edgeData['rules']]);
        }

        return $dialogNodeOption;
    }

    public function destroyOption(Dialog $dialog, DialogNode $dialogNode, DialogNodeOption $dialogNodeOption)
    {
        if (! $dialogNodeOption->node()->is($dialogNode)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if ($dialogNode->options()->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć jedynej opcji dialogowej',
            ]);
        }

        $dialogNodeOption->edges()->delete();
        $dialogNodeOption->delete();
    }

    public function destroyNode(Dialog $dialog, DialogNode $dialogNode)
    {
        if (! $dialogNode->dialog()->is($dialog)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if ($dialogNode->type == 'start') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć startowej kwesti dialogowej',
            ]);
        }

        if ($dialogNode->type == 'special' && $dialog->nodes()->where('type', 'special')->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć jedynej kwesti dialogowej',
            ]);
        }

        // First delete all edges associated with each option
        foreach ($dialogNode->options as $option) {
            $option->edges()->delete();
        }

        $dialogNode->directOutputEdges()->delete();

        // Then delete the options
        $dialogNode->options()->delete();

        $dialogNode->sourceEdges()->delete();

        // Finally delete the node itself
        $dialogNode->delete();
    }

    public function destroyEdge(Dialog $dialog, \App\Models\DialogEdge $dialogEdge)
    {
        if (! $dialogEdge->sourceDialog()->is($dialog)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if ($dialogEdge->source_option_id === null) {
            $startNodeId = $dialog->nodes()->where('type', 'start')->value('id');

            if (
                $startNodeId &&
                (
                    (int) $dialogEdge->source_node_id === (int) $startNodeId ||
                    $dialogEdge->source_node_id === null
                ) &&
                $dialog->edges()
                    ->whereNull('source_option_id')
                    ->where(function ($query) use ($startNodeId) {
                        $query->where('source_node_id', $startNodeId)
                            ->orWhereNull('source_node_id');
                    })
                    ->count() <= 1
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'message' => 'Nie możesz usunąć jedynego połączenia ze startowym dialogiem',
                ]);
            }
        }

        $dialogEdge->delete();
    }

    public function assignShop(Dialog $dialog, DialogNode $dialogNode, int $shopId)
    {
        if ($dialogNode->type != 'shop') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Sklep może zostac podpięty wyłącznie do kwesti dialogowej typu "sklep"',
            ]);
        }

        $dialogNode->shop_id = $shopId;
        $dialogNode->save();

        return $dialogNode;
    }

    public function search(string $query = '')
    {
        return $this->dialogModel->where('name', 'like', '%'.$query.'%')->limit(10)->get();
    }

    public function updateStartNodeEdges(Dialog $dialog, DialogNode $dialogNode, array $validated)
    {
        if ($dialogNode->type !== 'start' && $dialogNode->type !== 'randomizer') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Tylko węzeł startowy lub losowania może mieć reguły przejścia do kolejnych dialogów.',
            ]);
        }

        $edgesData = $validated['edges'];
        $edges = $dialogNode->getEdges();

        foreach ($edgesData as $edgeData) {
            $foundEdge = $edges->where('id', $edgeData['edge_id'])->first();
            if (! $foundEdge) {
                continue;
            }

            $foundEdge->update(['rules' => $edgeData['rules']]);
        }

        return $dialogNode;
    }

    /**
     * Copy a dialog node with its options and connections
     *
     * @return DialogNode The newly created node
     */
    public function copyNode(Dialog $dialog, DialogNode $dialogNode)
    {
        // Create a new node with the same properties as the original
        $newNode = $dialog->nodes()->create([
            'type' => $dialogNode->type,
            'content' => $dialogNode->content,
            'position' => [
                'x' => $dialogNode->position['x'] + 50, // Offset slightly to make it visible
                'y' => $dialogNode->position['y'] + 50,
            ],
            'action_data' => $dialogNode->action_data,
            'shop_id' => $dialogNode->shop_id,
        ]);

        // Copy all options
        foreach ($dialogNode->options as $option) {
            $newOption = $newNode->options()->create([
                'label' => $option->label,
                'additional_action' => $option->additional_action,
                'rules' => $option->rules,
            ]);

            // Copy all edges from this option
            foreach ($option->edges as $edge) {
                $newEdge = $dialog->edges()->make();
                $newEdge->sourceOption()->associate($newOption);
                $newEdge->targetNode()->associate($edge->targetNode);
                $newEdge->rules = $edge->rules;
                $newEdge->save();
            }
        }

        if (in_array($dialogNode->type, ['start', 'randomizer'], true)) {
            $directEdges = $dialogNode->getEdges();
            foreach ($directEdges as $edge) {
                $newEdge = $dialog->edges()->make();
                $newEdge->sourceNode()->associate($newNode);
                $newEdge->targetNode()->associate($edge->targetNode);
                $newEdge->rules = $edge->rules;
                $newEdge->save();
            }
        }

        return $newNode->fresh(['options.edges.targetNode']);
    }

    /**
     * Copy an entire dialog with all its nodes, options, and connections
     *
     * @param  Dialog  $dialog  The dialog to copy
     * @return Dialog The newly created dialog
     */
    public function copyDialog(Dialog $dialog)
    {
        // Create a new dialog with the same name + " - kopia"
        $newDialog = Dialog::create([
            'name' => $dialog->name.' - kopia',
        ]);

        // Load all nodes with their options and edges
        $dialog->load([
            'nodes' => function ($query) {
                $query->with(['options' => function ($query) {
                    $query->with(['edges' => function ($query) {
                        $query->with('targetNode');
                    }]);
                }]);
            },
            'edges' => function ($query) {
                $query->whereNull('source_option_id')->with('targetNode');
            },
        ]);

        // Map of original node IDs to new node IDs
        $nodeMap = [];
        // Map of original option IDs to new options
        $optionMap = [];

        // First pass: Create all nodes without connections
        foreach ($dialog->nodes as $node) {
            $newNode = $newDialog->nodes()->create([
                'type' => $node->type,
                'content' => $node->content,
                'position' => $node->position,
                'action_data' => $node->action_data,
                'shop_id' => $node->shop_id,
            ]);

            $nodeMap[$node->id] = $newNode->id;

            // Copy all options without edges
            foreach ($node->options as $option) {
                $newOption = $newNode->options()->create([
                    'label' => $option->label,
                    'additional_action' => $option->additional_action,
                    'rules' => $option->rules,
                ]);

                // Store the mapping of original option ID to new option
                $optionMap[$option->id] = $newOption;
            }
        }

        // Second pass: Create all edges with correct connections
        foreach ($dialog->nodes as $node) {
            foreach ($node->options as $option) {
                $newOption = $optionMap[$option->id];

                foreach ($option->edges as $edge) {
                    // Find the target node in the new dialog
                    $targetNodeId = $nodeMap[$edge->target_node_id] ?? null;

                    if ($targetNodeId) {
                        $newEdge = $newDialog->edges()->make();
                        $newEdge->sourceOption()->associate($newOption);
                        $newEdge->targetNode()->associate($targetNodeId);
                        $newEdge->rules = $edge->rules;
                        $newEdge->save();
                    }
                }
            }
        }

        foreach ($dialog->edges as $edge) {
            $legacyStartSourceNodeId = null;
            if ($edge->source_node_id === null) {
                $legacyStartSourceNodeId = $dialog->nodes->firstWhere('type', 'start')?->id;
            }

            $sourceNodeId = $edge->source_node_id
                ? ($nodeMap[$edge->source_node_id] ?? null)
                : ($legacyStartSourceNodeId ? ($nodeMap[$legacyStartSourceNodeId] ?? null) : null);
            $targetNodeId = $nodeMap[$edge->target_node_id] ?? null;

            if (! $sourceNodeId || ! $targetNodeId) {
                continue;
            }

            $newEdge = $newDialog->edges()->make();
            $newEdge->source_node_id = $sourceNodeId;
            $newEdge->target_node_id = $targetNodeId;
            $newEdge->rules = $edge->rules;
            $newEdge->save();
        }

        return $newDialog->fresh(['nodes.options.edges.targetNode']);
    }
}
