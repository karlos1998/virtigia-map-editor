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
        Schema::create('npc_groups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::table('npcs', function (Blueprint $table) {
            $table->foreignId('group_id')
                ->nullable()
                ->constrained('npc_groups')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('npc_groups');
    }
};
