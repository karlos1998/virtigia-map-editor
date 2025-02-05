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
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('x');
            $table->integer('y');

            $table->string('src');

            $table->string('name');

            $table->integer('pvp');

            $table->text('water');

            $table->string('battleground');

            $table->string('welcome')->nullable();

            $table->text('col');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
