<?php

namespace App\Http\Controllers;

use App\Enums\DialogNodeAdditionalAction;
use App\Enums\DialogNodeOptionAdditionalAction;
use App\Enums\DialogNodeOptionRule;
use App\Http\Requests\AssignShopToDialogNodeRequest;
use App\Http\Requests\MoveDialogNodeRequest;
use App\Http\Requests\StoreDialogEdgeRequest;
use App\Http\Requests\StoreDialogNodeOptionRequest;
use App\Http\Requests\StoreDialogNodeRequest;
use App\Http\Requests\StoreDialogRequest;
use App\Http\Requests\UpdateDialogNodeActionDataRequest;
use App\Http\Requests\UpdateDialogNodeOptionRequest;
use App\Http\Requests\UpdateDialogNodeRequest;
use App\Http\Requests\UpdateDialogRequest;
use App\Http\Requests\UpdateStartNodeEdgesRequest;
use App\Http\Resources\DialogEdgeResource;
use App\Http\Resources\DialogNodeOptionResource;
use App\Http\Resources\DialogNodeResource;
use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Services\DialogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DialogController extends Controller
{
    public function __construct(private readonly DialogService $dialogService) {}

    public function index()
    {
        return Inertia::render('Dialog/Index', [
            'dialogs' => $this->dialogService->getAll(),
        ]);
    }

    public function store(StoreDialogRequest $request)
    {
        $dialog = $this->dialogService->store($request->validated());

        return to_route('dialogs.show', $dialog->id);
    }

    public function update(Dialog $dialog, UpdateDialogRequest $request)
    {
        $this->dialogService->update($dialog, $request->validated());
    }

    public function show(Dialog $dialog): \Inertia\Response
    {
        // Eager load all relationships to improve performance
        $dialog->load([
            'nodes' => function ($query) {
                $query->with(['options' => function ($query) {
                    $query->with(['edges' => function ($query) {
                        $query->with('targetNode');
                    }]);
                }, 'shop']);
            },
            'edges' => function ($query) {
                $query->with(['sourceOption' => function ($query) {
                    $query->with('node');
                }, 'sourceNode', 'targetNode']);
            },
        ]);

        // Preload maps for teleportation nodes to avoid N+1 queries
        $teleportationNodes = $dialog->nodes->where('type', 'teleportation');
        $mapIds = [];

        foreach ($teleportationNodes as $node) {
            if (isset($node->action_data['teleportation']['mapId'])) {
                $mapIds[] = $node->action_data['teleportation']['mapId'];
            }
        }

        // If there are any map IDs, preload those maps
        $maps = [];
        if (! empty($mapIds)) {
            $maps = \App\Models\Map::whereIn('id', $mapIds)->get()->keyBy('id');
        }

        // Share the preloaded maps with the DialogNodeResource
        \App\Http\Resources\DialogNodeResource::$maps = $maps;

        return Inertia::render('Dialog/Show', [
            'dialog' => $dialog->only(['id', 'name']),
            'nodes' => DialogNodeResource::collection($dialog->nodes),
            'edges' => DialogEdgeResource::collection($dialog->edges),
            'dialogNodeOptionAdditionalActionsList' => DialogNodeOptionAdditionalAction::toDropdownList(),
            'availableRules' => DialogNodeOptionRule::list(),
            'dialogNodeAdditionalActionsList' => DialogNodeAdditionalAction::toDropdownList(),
        ]);
    }

    public function addNode(Dialog $dialog, StoreDialogNodeRequest $request)
    {
        $node = $this->dialogService->addNode($dialog, $request->validated());

        return response()->json([
            'node' => DialogNodeResource::make($node),
        ]);
    }

    public function moveNode(Dialog $dialog, DialogNode $dialogNode, MoveDialogNodeRequest $request)
    {
        $this->dialogService->moveNode($dialogNode, $request->validated());
    }

    public function addEdge(Dialog $dialog, StoreDialogEdgeRequest $request)
    {
        $edge = $this->dialogService->addEdge($dialog, $request->validated());

        return response()->json([
            'edge' => DialogEdgeResource::make($edge),
        ]);
    }

    public function destroyEdge(Dialog $dialog, DialogEdge $dialogEdge)
    {
        $this->dialogService->destroyEdge($dialog, $dialogEdge);
    }

    public function addOption(Dialog $dialog, DialogNode $dialogNode, StoreDialogNodeOptionRequest $request)
    {
        return response()->json([
            'option' => DialogNodeOptionResource::make($this->dialogService->addOption($dialogNode)),
        ]);
    }

    public function updateAction(Dialog $dialog, DialogNode $dialogNode, UpdateDialogNodeActionDataRequest $request)
    {
        return response()->json([
            'dialogNode' => DialogNodeResource::make(
                $this->dialogService->updateAction($dialog, $dialogNode, $request->validated())
            ),
        ]);
    }

    public function updateNode(Dialog $dialog, DialogNode $dialogNode, UpdateDialogNodeRequest $request)
    {
        $this->dialogService->updateNode($dialog, $dialogNode, $request->validated());
    }

    public function updateOption(Dialog $dialog, DialogNode $dialogNode, DialogNodeOption $dialogNodeOption, UpdateDialogNodeOptionRequest $request)// : JsonResponse
    {
        return response()->json(DialogNodeOptionResource::make($this->dialogService->updateOption($dialog, $dialogNode, $dialogNodeOption, $request->validated())));
        //        $this->dialogService->updateOption($dialog, $dialogNode, $dialogNodeOption, $request->validated());
    }

    public function destroyOption(Dialog $dialog, DialogNode $dialogNode, DialogNodeOption $dialogNodeOption): \Illuminate\Http\Response
    {
        $this->dialogService->destroyOption($dialog, $dialogNode, $dialogNodeOption);

        return response()->noContent();
    }

    public function destroyNode(Dialog $dialog, DialogNode $dialogNode)
    {
        $this->dialogService->destroyNode($dialog, $dialogNode);
    }

    /**
     * @throws ValidationException
     */
    public function assignShop(Dialog $dialog, DialogNode $dialogNode, AssignShopToDialogNodeRequest $request)
    {
        $node = $this->dialogService->assignShop($dialog, $dialogNode, $request->get('shop_id'));

        return response()->json([
            'dialogNode' => DialogNodeResource::make($node),
        ]);
    }

    public function search(Request $request)
    {
        return response()->json($this->dialogService->search($request->get('query', '')));
    }

    public function updateStartNodeEdges(Dialog $dialog, DialogNode $dialogNode, UpdateStartNodeEdgesRequest $request)
    {
        $node = $this->dialogService->updateStartNodeEdges($dialog, $dialogNode, $request->validated());

        return response()->json([
            'dialogNode' => DialogNodeResource::make($node),
        ]);
    }

    /**
     * Update order of options for a dialog node
     */
    public function updateOptionsOrder(Request $request, Dialog $dialog, DialogNode $dialogNode)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            return response()->json(['message' => 'Invalid payload'], 422);
        }

        foreach ($ids as $i => $id) {
            DialogNodeOption::where('id', $id)
                ->where('node_id', $dialogNode->id)
                ->update(['order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Copy a dialog node with its options and connections
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function copyNode(Dialog $dialog, DialogNode $dialogNode)
    {
        $newNode = $this->dialogService->copyNode($dialog, $dialogNode);

        return response()->json([
            'node' => DialogNodeResource::make($newNode),
        ]);
    }

    /**
     * Copy an entire dialog with all its nodes, options, and connections
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function copyDialog(Dialog $dialog)
    {
        $newDialog = $this->dialogService->copyDialog($dialog);

        return to_route('dialogs.show', $newDialog->id);
    }
}
