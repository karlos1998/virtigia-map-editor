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
        Schema::create('base_items', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('stats', 1024)->nullable();
            $table->string('pr')->nullable();
            $table->string('cl')->nullable();
            $table->string('src')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_items');
    }
};
