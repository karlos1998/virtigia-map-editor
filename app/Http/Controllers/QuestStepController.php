<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestStepRequest;
use App\Http\Requests\UpdateQuestStepRequest;
use App\Http\Resources\QuestStepResource;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Services\QuestStepService;
use Illuminate\Http\Request;

class QuestStepController extends Controller
{
    /**
     * @var QuestStepService
     */
    protected $questStepService;

    /**
     * Create a new controller instance.
     *
     * @param QuestStepService $questStepService
     */
    public function __construct(QuestStepService $questStepService)
    {
        $this->questStepService = $questStepService;
    }
    /**
     * Get a specific quest step by ID.
     */
    public function show(QuestStep $step)
    {
        $questStep = $this->questStepService->getQuestStep($step);

        return response()->json([
            'step' => QuestStepResource::make($questStep),
        ]);
    }

    /**
     * Store a newly created quest step in storage.
     */
    public function store(StoreQuestStepRequest $request, Quest $quest)
    {
        $questStep = $this->questStepService->createQuestStep($quest, $request->validated());

    }

    /**
     * Update the specified quest step in storage.
     */
    public function update(UpdateQuestStepRequest $request, Quest $quest, QuestStep $step)
    {
        $updatedStep = $this->questStepService->updateQuestStep($step, $request->validated());
    }

    /**
     * Remove the specified quest step from storage.
     */
    public function destroy(Quest $quest, QuestStep $step)
    {
        $this->questStepService->deleteQuestStep($step);

    }
}
