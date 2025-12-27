<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('base_npc_special_attacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_npc_id')->constrained()->onDelete('cascade');
            $table->foreignId('special_attack_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['base_npc_id', 'special_attack_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_npc_special_attacks');
    }
};
