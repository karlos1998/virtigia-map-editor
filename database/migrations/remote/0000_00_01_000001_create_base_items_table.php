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
            $table->timestamps();

            $table->string('name');

            $table->string('src');
            $table->text('stats');
            $table->integer('cl')->default(0);
            $table->integer('pr')->default(0);
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
