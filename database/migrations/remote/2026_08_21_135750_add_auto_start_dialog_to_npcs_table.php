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
        Schema::table('npcs', function (Blueprint $table) {
            $table->boolean('auto_start_dialog')->default(false)->after('dialog_id');
            $table->unsignedTinyInteger('auto_start_dialog_range')->default(1)->after('auto_start_dialog');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn(['auto_start_dialog', 'auto_start_dialog_range']);
        });
    }
};
