<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_item_id')->nullable()->after('max_buy_price');
            $table->foreign('currency_item_id')->references('id')->on('base_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['currency_item_id']);
            $table->dropColumn('currency_item_id');
        });
    }
};
