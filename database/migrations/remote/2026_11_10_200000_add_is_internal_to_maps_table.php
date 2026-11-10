<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Internal maps (administration, testing) are skipped by player-facing calculations:
 * quest walkthroughs, hero-finding scrolls and similar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false);
        });

        DB::table('maps')->where('name', 'Siedziba MG')->update(['is_internal' => true]);
    }

    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
