<?php

namespace App\Http\Controllers;

use App\DTO\DialogDto;
use App\Services\DialogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DialogController extends Controller
{
    protected DialogService $service;

    public function __construct(DialogService $service)
    {
        $this->service = $service;
    }

    public function index(): Response
    {
        return Inertia::render('Dialogs');
    }

    public function store(Request $request, int $groupId): JsonResponse
    {
        $dialogDto = new DialogDto($request->all());
        $dialog = $this->service->createDialog($groupId, $dialogDto);
        return response()->json($dialog, 201);
    }

    public function show(int $dialogId): JsonResponse
    {
        $dialog = $this->service->getDialog($dialogId);
        return response()->json($dialog, 200);
    }

    public function destroy(int $dialogId): JsonResponse
    {
        $this->service->deleteDialog($dialogId);
        return response()->json(null, 204);
    }

    public function update(Request $request, int $dialogId): JsonResponse
    {
        $dialogDto = new DialogDto($request->all());
        $dialog = $this->service->updateDialog($dialogId, $dialogDto);
        return response()->json($dialog, 200);
    }
}
