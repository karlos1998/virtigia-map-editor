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
        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

//            $table->foreignId('map_id')->constrained('maps');
//
//            $table->string('name');
//
//            $table->integer('qm');
//
//            $table->string('src');
//
//            $table->integer('x');
//            $table->integer('y');
//
//            $table->integer('lvl')->default(0);
//
//
//            //todo - te 3 beda do wywalenia raczej
//            $table->integer('type')->default(0);
//
//            $table->integer('wt')->default(0);

            $table->integer('grp');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('npcs');
    }
};
