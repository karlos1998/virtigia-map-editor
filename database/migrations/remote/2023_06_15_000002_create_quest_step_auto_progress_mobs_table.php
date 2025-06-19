<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quest_step_auto_progress_mobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_step_auto_progress_id');
            $table->foreign('quest_step_auto_progress_id')
                  ->references('id')
                  ->on('quest_step_auto_progresses')
                  ->onDelete('cascade')
                  ->name('fk_quest_step_auto_progress');
            $table->foreignId('base_npc_id');
            $table->foreign('base_npc_id')
                  ->references('id')
                  ->on('base_npcs')
                  ->onDelete('cascade')
                  ->name('fk_base_npc');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quest_step_auto_progress_mobs');
    }
};
