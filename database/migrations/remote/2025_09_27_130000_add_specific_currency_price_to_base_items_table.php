<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('base_items', function (Blueprint $table) {
            $table->unsignedInteger('specific_currency_price')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('base_items', function (Blueprint $table) {
            $table->dropColumn('specific_currency_price');
        });
    }
};
