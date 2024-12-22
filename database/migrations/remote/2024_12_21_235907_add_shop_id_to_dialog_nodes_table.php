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
        Schema::table('dialog_nodes', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->constrained('shops');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialog_nodes', function (Blueprint $table) {
            $table->dropColumn('shop_id');
        });
    }
};
