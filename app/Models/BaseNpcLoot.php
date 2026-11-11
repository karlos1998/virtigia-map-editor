<?php

namespace App\Models;

class BaseNpcLoot extends DynamicModel
{
    /** Elites, heroes and titans from this level never destroy loot on a level gap. */
    public const RANKED_NPC_KEEPS_LOOT_FROM_LEVEL = 250;

    /** Normal monsters from this level never destroy loot on a level gap. */
    public const NORMAL_NPC_KEEPS_LOOT_FROM_LEVEL = 270;

    public static function suggestDestroyOnLevelGap(BaseNpc $npc, BaseItem $item): bool
    {
        if ($npc->rank === \App\Enums\BaseNpcRank::NORMAL && $npc->lvl <= 15) {
            return false;
        }

        $keepsLootFrom = $npc->rank === \App\Enums\BaseNpcRank::NORMAL
            ? self::NORMAL_NPC_KEEPS_LOOT_FROM_LEVEL
            : self::RANKED_NPC_KEEPS_LOOT_FROM_LEVEL;
        if ($npc->lvl >= $keepsLootFrom) {
            return false;
        }

        return $item->suggest_destroy_on_level_gap
            ?? ! in_array($item->category, [\App\Enums\BaseItemCategory::NEUTRALS, \App\Enums\BaseItemCategory::QUESTS], true);
    }
}
