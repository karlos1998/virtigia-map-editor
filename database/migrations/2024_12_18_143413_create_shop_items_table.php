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
        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('item_id')
                ->constrained('base_items')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedTinyInteger('position');
            $table->unique(['shop_id', 'position']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_items');
    }
};
