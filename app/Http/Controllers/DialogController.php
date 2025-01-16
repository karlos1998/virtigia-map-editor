<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveDialogNodeRequest;
use App\Http\Requests\StoreDialogEdgeRequest;
use App\Http\Requests\StoreDialogNodeOptionRequest;
use App\Http\Requests\StoreDialogNodeRequest;
use App\Http\Requests\StoreDialogRequest;
use App\Http\Requests\UpdateDialogNodeActionDataRequest;
use App\Http\Resources\DialogEdgeResource;
use App\Http\Resources\DialogNodeOptionResource;
use App\Http\Resources\DialogNodeResource;
use App\Models\Dialog;
use App\Models\DialogNode;
use App\Services\DialogService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class DialogController extends Controller
{
    public function __construct(private readonly DialogService $dialogService)
    {
    }

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

    public function show(Dialog $dialog): \Inertia\Response
    {
        return Inertia::render('Dialog/Show', [
            'dialog' => $dialog->only('id'),
            'nodes' => DialogNodeResource::collection($dialog->nodes),
            'edges' => DialogEdgeResource::collection($dialog->edges),
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
}
