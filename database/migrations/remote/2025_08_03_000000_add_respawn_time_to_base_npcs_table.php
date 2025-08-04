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
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->integer('min_respawn_time')->nullable();
            $table->integer('max_respawn_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->dropColumn(['min_respawn_time', 'max_respawn_time']);
        });
    }
};
