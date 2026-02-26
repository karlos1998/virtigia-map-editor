<?php

namespace App\Models;

use App\Traits\JsonQueryHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class DialogNode extends DynamicModel
{
    use HasFactory, JsonQueryHelpers;

    protected $fillable = ['content', 'type', 'position', 'action_data', 'additional_actions'];

    protected $casts = [
        'position' => 'json',
        'action_data' => 'json',
        'additional_actions' => 'json',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(DialogNodeOption::class, 'node_id')->orderBy('order');
    }

    public function dialog(): HasOne
    {
        return $this->hasOne(Dialog::class, 'id', 'source_dialog_id');
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'id', 'shop_id');
    }

    public function sourceEdges(): HasMany
    {
        return $this->hasMany(DialogEdge::class, 'target_node_id');
    }

    public function directOutputEdges(): HasMany
    {
        return $this->hasMany(DialogEdge::class, 'source_node_id');
    }

    // tylko dla type start / randomizer
    public function getEdges(): Collection
    {
        if (! in_array($this->type, ['start', 'randomizer'], true)) {
            throw new \Exception('Próbowano pobrać edges do node, który nie wspiera bezpośrednich wyjść.');
        }

        return DialogEdge::where('source_dialog_id', $this->source_dialog_id)
            ->whereNull('source_option_id')
            ->where(function ($query) {
                $query->where('source_node_id', $this->id);

                if ($this->type === 'start') {
                    $query->orWhereNull('source_node_id');
                }
            })
            ->with('targetNode')
            ->get();
    }

    /**
     * Get all dialog edges where a quest or its steps are used in rules.
     *
     * @param  Quest|null  $quest  The quest to search for
     */
    public function getQuestEdges(?Quest $quest = null): Collection
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
            ->where('source_dialog_id', $this->source_dialog_id)
            ->where(function ($query) use ($allIds, $rulePaths) {
                $this->scopeWhereJsonContainsAnyInPaths($query, 'rules', $rulePaths, $allIds->all());
            })
            ->get();
    }
}
