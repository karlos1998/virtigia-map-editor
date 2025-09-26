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
        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedSmallInteger('buy_price_percent')->default(75)->comment('% ceny skupu, zakres 0-100');
            $table->unsignedSmallInteger('sell_price_percent')->default(120)->comment('% ceny sprzedaży, zakres 100-1000');
            $table->unsignedInteger('max_buy_price')->default(100000)->comment('Max cena skupu za przedmiot, zakres 0-1000000');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('buy_price_percent');
            $table->dropColumn('sell_price_percent');
            $table->dropColumn('max_buy_price');
        });
    }
};
