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
        Schema::create('npc_locations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('npc_id')
                ->constrained('npcs')
                ->references('id')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('map_id')
                ->constrained('maps')
                ->references('id')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->integer('x');

            $table->integer('y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('npc_locations');
    }
};
