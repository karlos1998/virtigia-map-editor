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
        Schema::table('base_items', function (Blueprint $table) {
            $table->json('attribute_points')->nullable()->comment('JSON map of attribute_key => base_item_id')->after('attributes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_items', function (Blueprint $table) {
            $table->dropColumn('attribute_points');
        });
    }
};
