<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quest_step_auto_progress_mobs', function (Blueprint $table) {
            $table->unsignedBigInteger('base_npc_id')->nullable()->change();
            $table->foreignId('mob_species_id')->nullable()->after('base_npc_id')->constrained('mob_species')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quest_step_auto_progress_mobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mob_species_id');
            $table->unsignedBigInteger('base_npc_id')->nullable(false)->change();
        });
    }
};

