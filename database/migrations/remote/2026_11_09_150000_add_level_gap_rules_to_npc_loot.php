<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('base_items', function (Blueprint $table): void {
            $table->boolean('suggest_destroy_on_level_gap')->default(true);
        });

        Schema::table('base_npc_loots', function (Blueprint $table): void {
            $table->boolean('destroy_on_level_gap')->default(true);
        });

        DB::table('base_items')->whereIn('category', ['quests', 'neutrals'])
            ->update(['suggest_destroy_on_level_gap' => false]);

        DB::table('base_npc_loots')
            ->whereIn('base_item_id', DB::table('base_items')->select('id')->where('suggest_destroy_on_level_gap', false))
            ->orWhereIn('base_npc_id', DB::table('base_npcs')->select('id')->where('rank', 'NORMAL')->where('lvl', '<=', 15))
            ->update(['destroy_on_level_gap' => false]);
    }

    public function down(): void
    {
        Schema::table('base_npc_loots', fn (Blueprint $table) => $table->dropColumn('destroy_on_level_gap'));
        Schema::table('base_items', fn (Blueprint $table) => $table->dropColumn('suggest_destroy_on_level_gap'));
    }
};
