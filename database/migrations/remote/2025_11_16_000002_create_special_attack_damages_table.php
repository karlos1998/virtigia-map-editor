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
        Schema::create('special_attack_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_attack_id')->constrained()->onDelete('cascade');
            $table->enum('element', ['physical', 'lightning', 'fire', 'ranged']);
            $table->integer('min_damage');
            $table->integer('max_damage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_attack_damages');
    }
};
