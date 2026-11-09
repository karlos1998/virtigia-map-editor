<?php

namespace App\Models;

class BaseNpcLoot extends DynamicModel
{
    public static function suggestDestroyOnLevelGap(BaseNpc $npc, BaseItem $item): bool
    {
        if ($npc->rank === \App\Enums\BaseNpcRank::NORMAL && $npc->lvl <= 15) {
            return false;
        }

        return $item->suggest_destroy_on_level_gap
            ?? ! in_array($item->category, [\App\Enums\BaseItemCategory::NEUTRALS, \App\Enums\BaseItemCategory::QUESTS], true);
    }
}
