<?php

use App\Enums\Profession;
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
        if (Schema::hasTable('base_npcs')) {
            return;
        }

        Schema::create('base_npcs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('name')->nullable();
            $table->string('src');
            $table->integer('lvl')->default(0);
            $table->integer('type')->default(0);
            $table->integer('wt')->default(0);
            $table->enum('profession', Profession::valuesToList())->default(Profession::w->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_npcs');
    }
};
