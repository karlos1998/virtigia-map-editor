<?php

namespace App\Models;

use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Traits\JsonQueryHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class QuestStep extends DynamicModel
{
    use HasFactory, JsonQueryHelpers;

    protected $fillable = [
        'name',
        'description',
        'quest_id',
        'visible_in_quest_list',
    ];

    protected $casts = [
        'visible_in_quest_list' => 'boolean',
    ];

    protected $attributes = [
        'visible_in_quest_list' => true,
    ];

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * Get the auto progress settings for this quest step.
     */
    public function autoProgress(): HasOne
    {
        return $this->hasOne(QuestStepAutoProgress::class);
    }

    /**
     * Get all dialogs where this quest step is used in dialog node options or additional actions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDialogs()
    {
        if (!$this->id) {
            return collect();
        }

        $stepId = 's-' . $this->id;
        $numericStepId = $this->id;
        $rulePaths = [
            '$.questBeforeStep.value',
            '$.questAfterStep.value',
            '$.questStep.value'
        ];

        return Dialog::distinct()
            ->whereHas('nodes.options', function ($query) use ($stepId, $rulePaths) {
                $this->scopeWhereJsonContainsInPaths($query, 'rules', $rulePaths, $stepId);
            })
            ->orWhereHas('nodes', function ($query) use ($stepId, $numericStepId) {
                // Check for string format with s- prefix
                $query->whereRaw('JSON_CONTAINS(additional_actions, ?, \'$.setQuestStep.value\')', [$stepId])
                    // Also check for numeric format without prefix
                    ->orWhereRaw('JSON_CONTAINS(additional_actions, ?, \'$.setQuestStep.value\')', [$numericStepId]);
            })
            ->get();
    }
    /**
     * Get all dialog nodes where this quest step is used in additional actions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNodes()
    {
        if (!$this->id) {
            return collect();
        }

        $stepId = $this->id;

        return DialogNode::distinct()
            ->where(function($query) use ($stepId) {
                $query->whereRaw('JSON_CONTAINS(additional_actions, ?, \'$.setQuestStep.value\')', [$stepId]);
            })
            ->get();
    }

    /**
     * Get all dialog node options where this quest step is used in rules.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNodeOptions()
    {
        if (!$this->id) {
            return collect();
        }

        $stepId = 's-' . $this->id;
        $rulePaths = [
            '$.questBeforeStep.value',
            '$.questAfterStep.value',
            '$.questStep.value'
        ];

        return DialogNodeOption::distinct()
            ->where(function($query) use ($stepId, $rulePaths) {
                $this->scopeWhereJsonContainsInPaths($query, 'rules', $rulePaths, $stepId);
            })
            ->get();
    }

    /**
     * Get all dialog edges where this quest step is used in rules.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEdges()
    {
        if (!$this->id) {
            return collect();
        }

        $stepId = 's-' . $this->id;
        $rulePaths = [
            '$.questBeforeStep.value',
            '$.questAfterStep.value',
            '$.questStep.value'
        ];

        return DialogEdge::distinct()
            ->where(function($query) use ($stepId, $rulePaths) {
                $this->scopeWhereJsonContainsInPaths($query, 'rules', $rulePaths, $stepId);
            })
            ->get();
    }
}
