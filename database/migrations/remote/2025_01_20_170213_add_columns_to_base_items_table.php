<?php

use App\Enums\BaseItemRarity;
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
        Schema::table('base_items', function (Blueprint $table) {
            $table->json('attributes')->nullable();
            $table->enum('category', \App\Enums\BaseItemCategory::valuesToList())->nullable();
            $table->enum('currency', \App\Enums\BaseItemCurrency::valuesToList())->nullable();
            $table->integer('price')->nullable();
            $table->enum('rarity', BaseItemRarity::valuesToList())->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_items', function (Blueprint $table) {
            //
        });
    }
};
