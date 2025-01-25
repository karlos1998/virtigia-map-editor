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
        Schema::create('respawn_points', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('map_id')
                ->nullable()
                ->constrained('maps')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->integer('x');
            $table->integer('y');

            $table->integer('max_steps'); //max ilosc mapek prowadzacych do tego respu przy automatycznym wyliczeniu
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respawn_points');
    }
};
