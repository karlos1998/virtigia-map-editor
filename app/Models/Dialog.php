<?php

namespace App\Models;

use App\Traits\JsonQueryHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * Get all dialog edges where a quest or its steps are used in rules.
     *
     * @param Quest|null $quest The quest to search for
     * @return Collection
     */
    public function getEdges(Quest $quest = null): Collection
    {
        if ($quest === null || !$quest->id) {
            return collect();
        }

        $questId = 'q-' . $quest->id;
        $questStepIds = $quest->steps->map(function($value) {
            return "s-$value->id";
        });

        $allIds = collect([$questId])->merge($questStepIds);
        $rulePaths = [
            '$.questBeforeStep.value',
            '$.questAfterStep.value',
            '$.questStep.value'
        ];

        return DialogEdge::distinct()
            ->where(function($query) use ($allIds, $rulePaths) {
                $this->scopeWhereJsonContainsAnyInPaths($query, 'rules', $rulePaths, $allIds->all());
            })
            ->get();
    }
}
