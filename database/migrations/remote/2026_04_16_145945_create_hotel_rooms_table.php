<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('base_item_id')->constrained('base_items');
            $table->foreignId('door_id')->constrained('doors');

            $table->unique(['hotel_id', 'door_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_rooms');
    }
};
