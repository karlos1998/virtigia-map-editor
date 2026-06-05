<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\QuestStep;

class QuestStepService
{
    /**
     * Create a new quest step.
     */
    public function createQuestStep(Quest $quest, array $data): QuestStep
    {
        return $quest->steps()->create($data);
    }

    /**
     * Get a quest step with its relationships.
     */
    public function getQuestStep(QuestStep $step): QuestStep
    {
        return $step->load(['quest', 'autoProgress.mobs.baseNpc', 'autoProgress.mobs.mobSpecies']);
    }

    /**
     * Delete a quest step.
     */
    public function deleteQuestStep(QuestStep $step): void
    {
        if (! $step->getDialogs()->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie można usunąć questa, ponieważ jest używany w dialogach.',
            ]);
        }
        $step->delete();
    }

    /**
     * Update a quest step with the given data.
     */
    public function updateQuestStep(QuestStep $step, array $data): QuestStep
    {
        // Update basic quest step data
        $step->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'visible_in_quest_list' => $data['visible_in_quest_list'] ?? false,
            'auto_advance_next_day' => $data['auto_advance_next_day'] ?? false,
            'auto_advance_to_step_id' => $data['auto_advance_to_step_id'] ?? null,
        ]);

        // Handle auto progress
        if ($data['auto_progress'] ?? false) {
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
                        'base_npc_id' => $mobData['type'] === 'base_npc' ? $mobData['base_npc_id'] : null,
                        'mob_species_id' => $mobData['type'] === 'mob_species' ? $mobData['mob_species_id'] : null,
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

        return $step->load('autoProgress.mobs.baseNpc', 'autoProgress.mobs.mobSpecies');
    }
}
