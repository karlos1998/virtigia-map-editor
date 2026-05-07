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
        Schema::create('seasonal_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('base_npc_seasonal_events', function (Blueprint $table) {
            $table->unsignedBigInteger('base_npc_id');
            $table->unsignedBigInteger('seasonal_event_id');

            $table->primary(['base_npc_id', 'seasonal_event_id'], 'base_npc_seasonal_events_pk');
            $table->foreign('base_npc_id')->references('id')->on('base_npcs')->cascadeOnDelete();
            $table->foreign('seasonal_event_id')->references('id')->on('seasonal_events')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_npc_seasonal_events');
        Schema::dropIfExists('seasonal_events');
    }
};

