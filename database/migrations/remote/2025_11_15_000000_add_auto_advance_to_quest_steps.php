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
        Schema::table('quest_steps', function (Blueprint $table) {
            $table->boolean('auto_advance_next_day')->default(false)->after('visible_in_quest_list');
            $table->unsignedBigInteger('auto_advance_to_step_id')->nullable()->after('auto_advance_next_day');

            $table->foreign('auto_advance_to_step_id')->references('id')->on('quest_steps')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quest_steps', function (Blueprint $table) {
            $table->dropForeign(['auto_advance_to_step_id']);
            $table->dropColumn(['auto_advance_to_step_id', 'auto_advance_next_day']);
        });
    }
};
