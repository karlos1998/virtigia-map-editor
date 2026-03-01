<?php

namespace App\Models;

use App\Traits\JsonQueryHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Quest extends DynamicModel
{
    use HasFactory, JsonQueryHelpers;

    protected $fillable = [
        'name',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(QuestStep::class);
    }

    public function isDaily(): bool
    {
        if ($this->relationLoaded('steps')) {
            return $this->steps->contains(fn ($step) => ($step->auto_advance_next_day ?? false) === true);
        }

        return $this->steps()->where('auto_advance_next_day', true)->exists();
    }

    /**
     * Get all dialogs where this quest is used in dialog node options or additional actions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDialogs()
    {
        if (! $this->id) {
            return collect();
        }

        $questId = 'q-'.$this->id;
        $questStepIds = $this->steps->map(function ($value) {
            return "s-$value->id";
        });

        $allIds = collect([$questId])->merge($questStepIds);
        $rulePaths = [
            '$.questBeforeStep.value',
            '$.questAfterStep.value',
            '$.questStep.value',
        ];

        $query = Dialog::distinct()
            ->whereHas('nodes.options', function ($query) use ($allIds, $rulePaths) {
                $this->scopeWhereJsonContainsAnyInPaths($query, 'rules', $rulePaths, $allIds->all());
            });

        // Only add the orWhereHas clause if there are quest steps
        if ($questStepIds->isNotEmpty()) {
            $query->orWhereHas('nodes', function ($query) use ($questStepIds) {
                // Handle matches for quest step IDs in additional_actions
                $query->where(function ($subQuery) use ($questStepIds) {
                    foreach ($questStepIds as $stepId) {
                        $this->scopeWhereJsonContains($subQuery, 'additional_actions', '$.setQuestStep.value', $stepId, 'or');
                    }
                });
            });
        }

        return $query->get();
    }

    /**
     * Get all dialog nodes where this quest or its steps are used in additional actions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNodes()
    {
        if (! $this->id) {
            return collect();
        }

        $questStepIds = $this->steps->map(function ($value) {
            return "s-$value->id";
        });

        // If there are no quest steps, return an empty collection
        if ($questStepIds->isEmpty()) {
            return collect();
        }

        return DialogNode::distinct()
            ->where(function ($query) use ($questStepIds) {
                // Handle matches for quest step IDs in additional_actions
                foreach ($questStepIds as $stepId) {
                    $this->scopeWhereJsonContains($query, 'additional_actions', '$.setQuestStep.value', $stepId, 'or');
                }
            })
            ->get();
    }

    /**
     * Get all dialog node options where this quest or its steps are used in rules.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNodeOptions()
    {
        if (! $this->id) {
            return collect();
        }

        $questId = 'q-'.$this->id;
        $questStepIds = $this->steps->map(function ($value) {
            return "s-$value->id";
        });

        $allIds = collect([$questId])->merge($questStepIds);
        $rulePaths = [
            '$.questBeforeStep.value',
            '$.questAfterStep.value',
            '$.questStep.value',
        ];

        return DialogNodeOption::distinct()
            ->where(function ($query) use ($allIds, $rulePaths) {
                $this->scopeWhereJsonContainsAnyInPaths($query, 'rules', $rulePaths, $allIds->all());
            })
            ->get();
    }

    /**
     * Get all dialog edges where this quest or its steps are used in rules.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEdges()
    {
        if (! $this->id) {
            return collect();
        }

        $questId = 'q-'.$this->id;
        $questStepIds = $this->steps->map(function ($value) {
            return "s-$value->id";
        });

        $allIds = collect([$questId])->merge($questStepIds);
        $rulePaths = [
            '$.questBeforeStep.value',
            '$.questAfterStep.value',
            '$.questStep.value',
        ];

        return DialogEdge::distinct()
            ->where(function ($query) use ($allIds, $rulePaths) {
                $this->scopeWhereJsonContainsAnyInPaths($query, 'rules', $rulePaths, $allIds->all());
            })
            ->get();
    }
}
