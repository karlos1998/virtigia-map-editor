<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuestResource;
use App\Models\Quest;
use App\Services\QuestService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestController extends Controller
{

    public function __construct(
        private readonly QuestService $questService,
    )
    {
    }

    /**
     * Display a listing of the quests.
     */
    public function index()
    {
        return Inertia::render('Quest/Index', [
            'quests' => $this->questService->findAll(),
        ]);
    }

    /**
     * Store a newly created quest in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $quest = Quest::create($validated);

        return to_route('quests.show', $quest->id);
    }

    /**
     * Display the specified quest.
     */
    public function show(Quest $quest)
    {
        $quest->load(['steps', 'steps.autoProgress']);

        return Inertia::render('Quest/Show', [
            'quest' => QuestResource::make($quest),
        ]);
    }

    /**
     * Update the specified quest in storage.
     */
    public function update(Request $request, Quest $quest)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $quest->update($validated);
    }

    /**
     * Remove the specified quest from storage.
     */
    public function destroy(Quest $quest)
    {
        $quest->delete();

        return to_route('quests.index');
    }

    /**
     * Search for quests by name.
     */
    public function search(Request $request)
    {
        $query = $request->get('query', '');

        $quests = Quest::where('name', 'like', "%{$query}%")->get();

        return response()->json(QuestResource::collection($quests));
    }

    /**
     * Get steps for a specific quest.
     */
    public function getSteps(Quest $quest)
    {
        return response()->json([
            'steps' => $quest->steps()->get()
        ]);
    }
}
