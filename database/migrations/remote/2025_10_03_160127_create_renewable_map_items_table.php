<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renewable_map_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('map_id');
            $table->unsignedBigInteger('base_item_id');
            $table->unsignedInteger('x');
            $table->unsignedInteger('y');
            $table->unsignedInteger('respawn_time_seconds');
            $table->timestamps();

            $table->foreign('map_id')->references('id')->on('maps')->onDelete('cascade');
            $table->foreign('base_item_id')->references('id')->on('base_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('renewable_map_items');
    }
};
