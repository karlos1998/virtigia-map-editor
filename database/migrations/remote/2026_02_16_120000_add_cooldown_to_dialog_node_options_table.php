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
        Schema::table('dialog_node_options', function (Blueprint $table) {
            $table->unsignedInteger('cooldown')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialog_node_options', function (Blueprint $table) {
            $table->dropColumn('cooldown');
        });
    }
};
