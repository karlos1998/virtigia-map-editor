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
        Schema::create('base_npc_loots', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('base_npc_id')
                ->constrained('base_npcs')
                ->references('id')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('base_item_id')
                ->constrained('base_items')
                ->references('id')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_npc_loots');
    }
};
