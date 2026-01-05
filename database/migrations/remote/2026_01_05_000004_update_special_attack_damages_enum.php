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
        Schema::table('special_attack_damages', function (Blueprint $table) {
            $table->enum('element', ['physical', 'lightning', 'fire', 'ranged', 'frost'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_attack_damages', function (Blueprint $table) {
            $table->enum('element', ['physical', 'lightning', 'fire', 'ranged'])->change();
        });
    }
};
