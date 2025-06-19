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
        Schema::create('quest_step_auto_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_step_id');
            $table->foreign('quest_step_id')
                  ->references('id')
                  ->on('quest_steps')
                  ->onDelete('cascade')
                  ->name('fk_quest_step');
            $table->string('type'); // 'time' or 'mobs'
            $table->integer('time_seconds')->nullable(); // Only used when type is 'time'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quest_step_auto_progresses');
    }
};
