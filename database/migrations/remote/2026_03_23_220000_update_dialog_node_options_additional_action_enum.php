<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dialog_node_options', function (Blueprint $table) {
            $table->enum('additional_action', [
                'ADD_LEVELS', //legacy
                'HEAL',
                'SELF_KILL',
                'SUBTRACT_EXP',
                'BATTLE',
                'KILL_AND_LOOT',
                'KILL',
                'SHOW_MAIL',
                'SHOW_DEPOSIT',
                'SHOW_CLAN_DEPOSIT',
                'SHOW_AUCTIONS',
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialog_node_options', function (Blueprint $table) {
            $table->enum('additional_action', [
                'ADD_LEVELS', //legacy
                'HEAL',
                'SELF_KILL',
                'SUBTRACT_EXP',
                'BATTLE',
                'KILL_AND_LOOT',
                'KILL',
                'SHOW_MAIL',
                'SHOW_DEPOSIT',
                'SHOW_AUCTIONS',
            ])->nullable()->change();
        });
    }
};
