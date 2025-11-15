<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\QuestStep;

class QuestStepService
{
    /**
     * Create a new quest step.
     *
     * @param Quest $quest
     * @param array $data
     * @return QuestStep
     */
    public function createQuestStep(Quest $quest, array $data): QuestStep
    {
        return $quest->steps()->create($data);
    }

    /**
     * Get a quest step with its relationships.
     *
     * @param QuestStep $step
     * @return QuestStep
     */
    public function getQuestStep(QuestStep $step): QuestStep
    {
        return $step->load(['quest', 'autoProgress.mobs.baseNpc']);
    }

    /**
     * Delete a quest step.
     *
     * @param QuestStep $step
     * @return void
     */
    public function deleteQuestStep(QuestStep $step): void
    {
        if (!$step->getDialogs()->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie można usunąć questa, ponieważ jest używany w dialogach.'
            ]);
        }
        $step->delete();
    }
    /**
     * Update a quest step with the given data.
     *
     * @param QuestStep $step
     * @param array $data
     * @return QuestStep
     */
    public function updateQuestStep(QuestStep $step, array $data): QuestStep
    {
        // Update basic quest step data
        $step->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'visible_in_quest_list' => $data['visible_in_quest_list'] ?? false,
            'auto_advance_next_day' => $data['auto_advance_next_day'] ?? false,
            'auto_advance_to_step_id' => $data['auto_advance_to_step_id'] ?? null,
        ]);

        // Handle auto progress
        if ($data['auto_progress']) {
            // Create or update auto progress
            $autoProgress = $step->autoProgress()->updateOrCreate(
                [], // Find by quest_step_id (implicit)
                [
                    'type' => $data['progress_type'],
                    'time_seconds' => $data['progress_type'] === 'time' ? $data['progress_time'] : null,
                ]
            );

            // Handle mobs if progress type is 'mobs'
            if ($data['progress_type'] === 'mobs') {
                // Delete existing mobs
                $autoProgress->mobs()->delete();

                // Add new mobs
                foreach ($data['progress_mobs'] as $mobData) {
                    $autoProgress->mobs()->create([
                        'base_npc_id' => $mobData['base_npc_id'],
                        'quantity' => $mobData['quantity'],
                    ]);
                }
            }
        } else {
            // Delete auto progress if it exists
            if ($step->autoProgress) {
                $step->autoProgress->mobs()->delete();
                $step->autoProgress->delete();
            }
        }

        return $step->load('autoProgress.mobs.baseNpc');
    }
}
