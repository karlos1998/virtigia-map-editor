<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('dialog_counter_id')->constrained('dialog_counters')->restrictOnDelete();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('map_track_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_track_id')->constrained('map_tracks')->cascadeOnDelete();
            $table->foreignId('map_id')->constrained('maps')->restrictOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('sequence');
            $table->timestamps();

            $table->unique(['map_track_id', 'sequence']);
            $table->index(['map_id', 'map_track_id']);
        });

        Schema::create('map_track_checkpoint_tiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_track_checkpoint_id')
                ->constrained('map_track_checkpoints')
                ->cascadeOnDelete();
            $table->unsignedInteger('x');
            $table->unsignedInteger('y');

            $table->unique(['map_track_checkpoint_id', 'x', 'y'], 'checkpoint_tile_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_track_checkpoint_tiles');
        Schema::dropIfExists('map_track_checkpoints');
        Schema::dropIfExists('map_tracks');
    }
};
