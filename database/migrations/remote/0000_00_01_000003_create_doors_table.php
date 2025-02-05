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
        Schema::create('doors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('map_id')->constrained('maps');
            $table->integer('x');
            $table->integer('y');

            $table->foreignId('go_map_id')->constrained('maps');
            $table->integer('go_x');
            $table->integer('go_y');

            $table->integer('min_lvl')->nullable();
            $table->integer('max_lvl')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doors');
    }
};
