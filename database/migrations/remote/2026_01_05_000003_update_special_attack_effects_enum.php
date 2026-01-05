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
        Schema::table('special_attack_effects', function (Blueprint $table) {
            $table->enum('type', ['pushBack', 'poison', 'physicalCritStrength', 'healing', 'stun', 'debuffSA', 'addSA', 'deepWound', 'healingPerTurn', 'addArmor', 'addResistancePercent'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_attack_effects', function (Blueprint $table) {
            $table->enum('type', ['pushBack', 'poison', 'physicalCritStrength', 'healing', 'stun', 'debuffSA', 'addSA', 'deepWound'])->change();
        });
    }
};
