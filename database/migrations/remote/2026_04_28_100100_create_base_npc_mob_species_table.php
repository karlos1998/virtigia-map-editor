<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_npc_mob_species', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_npc_id')->constrained('base_npcs')->onDelete('cascade');
            $table->foreignId('mob_species_id')->constrained('mob_species')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['base_npc_id', 'mob_species_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_npc_mob_species');
    }
};

