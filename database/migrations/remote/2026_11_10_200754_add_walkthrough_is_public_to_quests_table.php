<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public walkthroughs are the ones the player wiki may show; hidden ones stay editor-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->boolean('walkthrough_is_public')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn('walkthrough_is_public');
        });
    }
};
