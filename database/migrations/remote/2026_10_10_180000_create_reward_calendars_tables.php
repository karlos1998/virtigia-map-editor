<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zastępuje stary globalny "kalendarz nagród" (calendar_days + reward_calendar_items)
     * kalendarzami z przypisanymi dniami.
     */
    public function up(): void
    {
        Schema::dropIfExists('reward_calendar_items');
        Schema::dropIfExists('calendar_days');

        Schema::create('reward_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // null = kalendarz powtarza się co roku, wartość = jednorazowy kalendarz na dany rok
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('style', 32)->default('blue');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('reward_calendar_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_calendar_id')->constrained('reward_calendars')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->string('type', 32)->default('normal');
            // Dzień zwykły: konfiguracja nagród w formacie kuferka (mode, rewardBinding, minRewards, maxRewards, items)
            $table->json('rewards')->nullable();
            // Dzień specjalny: lista progów [{ minOpenedDays, rewards }]
            $table->json('tiers')->nullable();
            $table->timestamps();

            $table->unique(['reward_calendar_id', 'month', 'day'], 'reward_calendar_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_calendar_days');
        Schema::dropIfExists('reward_calendars');

        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('month');
            $table->smallInteger('year')->nullable();
            $table->string('name')->nullable();
            $table->unique(['day', 'month', 'year'], 'calendar_day_unique');
        });

        Schema::create('reward_calendar_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('calendar_day_id')->constrained('calendar_days')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('base_item_id')->constrained('base_items')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->index(['calendar_day_id']);
        });
    }
};
