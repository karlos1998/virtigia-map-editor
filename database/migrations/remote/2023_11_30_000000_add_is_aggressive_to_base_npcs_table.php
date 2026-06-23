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
        if (Schema::hasColumn('base_npcs', 'is_aggressive')) {
            return;
        }

        Schema::table('base_npcs', function (Blueprint $table) {
            $table->boolean('is_aggressive')->default(true)->after('profession');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('base_npcs', 'is_aggressive')) {
            return;
        }

        Schema::table('base_npcs', function (Blueprint $table) {
            $table->dropColumn('is_aggressive');
        });
    }
};
