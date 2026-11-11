<?php

use App\Models\BaseNpc;
use App\Models\BaseNpcLoot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the engine's hard-coded "level 250+" exception with data: high level NPCs get divine intervention
 * switched off and their loot is no longer destroyed on a level gap (normal monsters only from a higher level).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('base_npcs')
            ->where('lvl', '>=', BaseNpc::NO_DIVINE_INTERVENTION_FROM_LEVEL)
            ->update(['divine_intervention' => false]);

        DB::table('base_npc_loots')
            ->whereIn('base_npc_id', DB::table('base_npcs')->select('id')->where(fn ($query) => $query
                ->where(fn ($ranked) => $ranked
                    ->where('rank', '!=', 'NORMAL')
                    ->where('lvl', '>=', BaseNpcLoot::RANKED_NPC_KEEPS_LOOT_FROM_LEVEL))
                ->orWhere(fn ($normal) => $normal
                    ->where('rank', 'NORMAL')
                    ->where('lvl', '>=', BaseNpcLoot::NORMAL_NPC_KEEPS_LOOT_FROM_LEVEL))))
            ->update(['destroy_on_level_gap' => false]);
    }

    public function down(): void
    {
        // Data change only: previous per-NPC values are not recoverable.
    }
};
