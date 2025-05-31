<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuestStepResource;
use App\Models\Quest;
use App\Models\QuestStep;
use Illuminate\Http\Request;

class QuestStepController extends Controller
{
    /**
     * Store a newly created quest step in storage.
     */
    public function store(Request $request, Quest $quest)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $quest->steps()->create($validated);
    }

    /**
     * Update the specified quest step in storage.
     */
    public function update(Request $request, Quest $quest, QuestStep $step)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $step->update($validated);
    }

    /**
     * Remove the specified quest step from storage.
     */
    public function destroy(Quest $quest, QuestStep $step)
    {
        $step->delete();
    }
}
