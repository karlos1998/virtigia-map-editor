<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->text('drop_chances')
                ->nullable()
                ->comment('JSON array of drop chances [common, unique, heroic, legendary, artefact]')
                ->after('guaranteed_loot');
        });
    }

    public function down(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->dropColumn('drop_chances');
        });
    }
};
