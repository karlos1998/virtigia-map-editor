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
        Schema::create('quest_seasonal_events', function (Blueprint $table) {
            $table->foreignId('quest_id')->constrained('quests')->cascadeOnDelete();
            $table->foreignId('seasonal_event_id')->constrained('seasonal_events')->cascadeOnDelete();
            $table->primary(['quest_id', 'seasonal_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quest_seasonal_events');
    }
};
