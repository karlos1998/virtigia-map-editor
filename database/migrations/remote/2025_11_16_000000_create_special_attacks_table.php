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
        Schema::create('special_attacks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('attack_type', ['special', 'normal']);
            $table->integer('charge_turns')->default(0);
            $table->enum('target', ['single', 'all', 'self', 'line']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_attacks');
    }
};
