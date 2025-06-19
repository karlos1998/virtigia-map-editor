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
        Schema::table('quest_steps', function (Blueprint $table) {
            $table->boolean('visible_in_quest_list')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quest_steps', function (Blueprint $table) {
            $table->dropColumn('visible_in_quest_list');
        });
    }
};
