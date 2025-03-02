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
        Schema::create('spawn_points', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->enum('profession', \App\Enums\Profession::valuesToList());

            $table->foreignId('map_id')->nullable()->constrained('maps');

            $table->integer('x');
            $table->integer('y');

            $table->unique('profession');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spawn_points');
    }
};
