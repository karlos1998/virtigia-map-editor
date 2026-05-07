<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_minimap_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('map_id')->unique();
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->timestamps();

            $table->foreign('map_id')->references('id')->on('maps')->cascadeOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('world_minimap_nodes');
    }
};
