<?php

namespace App\Models;

use App\Traits\JsonQueryHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Dialog extends DynamicModel
{
    use HasFactory, JsonQueryHelpers;

    protected $fillable = [
        'name',
    ];

    public function edges(): HasMany
    {
        return $this->hasMany(DialogEdge::class, 'source_dialog_id');
    }

    public function nodes()
    {
        return $this->hasMany(DialogNode::class, 'source_dialog_id');
    }

    public function npcs()
    {
        return $this->hasMany(Npc::class);
    }

    public function getRelatedQuestIds(): Collection
    {
        $this->loadMissing(['nodes.options', 'edges']);

        $questIds = collect();
        $questStepIds = collect();

        $collectQuestReferences = function (?array $rules) use (&$questIds, &$questStepIds): void {
            if (! $rules) {
                return;
            }

            foreach (['questBeforeStep', 'questAfterStep', 'questStep'] as $ruleKey) {
                $value = data_get($rules, "{$ruleKey}.value");

                if (is_string($value)) {
                    $values = [$value];
                } elseif (is_array($value)) {
                    $values = $value;
                } else {
                    $values = [];
                }

                foreach ($values as $entry) {
                    if (! is_string($entry) || ! str_contains($entry, '-')) {
                        continue;
                    }

                    [$prefix, $id] = explode('-', $entry, 2);
                    if (! is_numeric($id)) {
                        continue;
                    }

                    if ($prefix === 'q') {
                        $questIds->push((int) $id);
                    }

                    if ($prefix === 's') {
                        $questStepIds->push((int) $id);
                    }
                }
            }
        };

        foreach ($this->nodes as $node) {
            $collectQuestReferences($node->additional_actions ?? []);

            $setQuestStepValue = data_get($node->additional_actions, 'setQuestStep.value');
            if (is_numeric($setQuestStepValue)) {
                $questStepIds->push((int) $setQuestStepValue);
            }

            foreach ($node->options as $option) {
                $collectQuestReferences($option->rules ?? []);
            }
        }

        foreach ($this->edges as $edge) {
            $collectQuestReferences($edge->rules ?? []);
        }

        if ($questStepIds->isNotEmpty()) {
            $questIds = $questIds->merge(
                QuestStep::query()
                    ->whereIn('id', $questStepIds->unique()->values())
                    ->pluck('quest_id')
            );
        }

        return $questIds
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    public function getRelatedQuests(): Collection
    {
        $questIds = $this->getRelatedQuestIds();

        if ($questIds->isEmpty()) {
            return collect();
        }

        return Quest::query()
            ->with('steps')
            ->whereIn('id', $questIds)
            ->get();
    }

    /**
     * Get all dialog edges where a quest or its steps are used in rules.
     *
     * @param  Quest|null  $quest  The quest to search for
     */
    public function getEdges(?Quest $quest = null): Collection
    {
        if ($quest === null || ! $quest->id) {
            return collect();
        }

        $questId = 'q-'.$quest->id;
        $questStepIds = $quest->steps->map(function ($value) {
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
