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
            $table->enum('category', \App\Enums\BaseNpcCategory::valuesToList())->default(\App\Enums\BaseNpcCategory::NPC->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
