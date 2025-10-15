<?php

namespace App\Services;

use App\Http\Resources\DialogResource;
use App\Models\Dialog;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

class DialogService extends BaseService
{

    public function __construct(private readonly Dialog $dialogModel)
    {
    }

    public function getAll()
    {
        return $this->fetchData(
            DialogResource::class,
            $this->dialogModel->with(['npcs', 'npcs.locations']),
            new \Karlos3098\LaravelPrimevueTableService\Services\TableService(
                globalFilterColumns: ['name'],
            )
        );
    }

    public function addNode(Dialog $dialog, array $data)
    {
        $node = $dialog->nodes()->create($data);

        $node->options()->create([
            'label' => 'Zakończ.',
        ]);

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

        if(!$data['sourceNodeIsInput']) {
            /**
             * @var DialogNode $sourceNode
             */
            $sourceNode = $dialog->nodes()->find($data['sourceNodeId']);

            /**
             * @var DialogNodeOption $sourceOption
             */
            $sourceOption = $sourceNode->options()->find($data['sourceOptionId']);

//            if ($sourceOption->edges()->count() > 1)
//            {
//                throw \Illuminate\Validation\ValidationException::withMessages([
//                    'message' => 'Opcja może mieć NA TEN MOMENT tylko 2 połączenia',
//                ]);
//            }

            $edge->sourceOption()->associate($sourceOption);
        } else {
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
            ]
        ]);

        $node = $dialog->nodes()->create([
            'position' => [
                'x' => 200,
                'y' => 100,
            ],
            'content' => 'Oto przykładowa kwestia dialogowa'
        ]);
        $node->options()->create(['label' => 'Zakończ rozmowę']);

        $dialog
            ->edges()->make()
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
        foreach($edgesData as $edgeData)
        {
            $foundEdge = $dialogNodeOption->edges->where('id', $edgeData['edge_id'])->first();
            if(!$foundEdge) continue;

            $foundEdge->update(['rules' => $edgeData['rules']]);
        }

        return $dialogNodeOption;
    }

    public function destroyOption(Dialog $dialog, DialogNode $dialogNode, DialogNodeOption $dialogNodeOption)
    {
        if(!$dialogNodeOption->node()->is($dialogNode)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if($dialogNode->options()->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć jedynej opcji dialogowej',
            ]);
        }

        $dialogNodeOption->edges()->delete();
        $dialogNodeOption->delete();
    }

    public function destroyNode(Dialog $dialog, DialogNode $dialogNode)
    {
        if(!$dialogNode->dialog()->is($dialog)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if($dialogNode->type == 'start' ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć startowej kwesti dialogowej',
            ]);
        }

        if($dialogNode->type == 'special' && $dialog->nodes()->where('type', 'special')->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć jedynej kwesti dialogowej',
            ]);
        }

        // First delete all edges associated with each option
        foreach ($dialogNode->options as $option) {
            $option->edges()->delete();
        }

        // Then delete the options
        $dialogNode->options()->delete();

        $dialogNode->sourceEdges()->delete();

        // Finally delete the node itself
        $dialogNode->delete();
    }

    public function destroyEdge(Dialog $dialog, \App\Models\DialogEdge $dialogEdge)
    {
        if(!$dialogEdge->sourceDialog()->is($dialog)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if($dialogEdge->source_option_id === null && $dialog->edges()->whereNull('source_option_id')->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć jedynego połączenia ze startowym dialogiem',
            ]);
        }

        $dialogEdge->delete();
    }

    public function assignShop(Dialog $dialog, DialogNode $dialogNode, int $shopId)
    {
        if($dialogNode->type != 'shop') {
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
        return $this->dialogModel->where('name', 'like', '%' . $query . '%')->limit(10)->get();
    }

    public function updateStartNodeEdges(Dialog $dialog, DialogNode $dialogNode, array $validated)
    {
        if ($dialogNode->type !== 'start') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Tylko węzeł startowy może mieć reguły przejścia do kolejnych dialogów',
            ]);
        }

        $edgesData = $validated['edges'];
        $edges = $dialogNode->getEdges();

        foreach($edgesData as $edgeData)
        {
            $foundEdge = $edges->where('id', $edgeData['edge_id'])->first();
            if(!$foundEdge) continue;

            $foundEdge->update(['rules' => $edgeData['rules']]);
        }

        return $dialogNode;
    }

    /**
     * Copy a dialog node with its options and connections
     *
     * @param Dialog $dialog
     * @param DialogNode $dialogNode
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

        return $newNode->fresh(['options.edges.targetNode']);
    }

    /**
     * Copy an entire dialog with all its nodes, options, and connections
     *
     * @param Dialog $dialog The dialog to copy
     * @return Dialog The newly created dialog
     */
    public function copyDialog(Dialog $dialog)
    {
        // Create a new dialog with the same name + " - kopia"
        $newDialog = Dialog::create([
            'name' => $dialog->name . ' - kopia',
        ]);

        // Load all nodes with their options and edges
        $dialog->load([
            'nodes' => function($query) {
                $query->with(['options' => function($query) {
                    $query->with(['edges' => function($query) {
                        $query->with('targetNode');
                    }]);
                }]);
            }
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

        return $newDialog->fresh(['nodes.options.edges.targetNode']);
    }
}
