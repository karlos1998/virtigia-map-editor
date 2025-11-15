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
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('month');
            $table->smallInteger('year')->nullable();
            $table->string('name')->nullable();

            // allow recurring days by having year nullable; uniqueness per day/month/year globally
            $table->unique(['day', 'month', 'year'], 'calendar_day_unique');
        });

        Schema::create('reward_calendar_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('calendar_day_id')
                ->constrained('calendar_days')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('base_item_id')
                ->constrained('base_items')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedInteger('quantity')->default(1);

            $table->index(['calendar_day_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_calendar_items');
        Schema::dropIfExists('calendar_days');
    }
};
