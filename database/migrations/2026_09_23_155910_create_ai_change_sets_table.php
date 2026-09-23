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
        Schema::create('ai_change_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('world', 64)->index();
            $table->string('title');
            $table->text('prompt')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('revision')->default(1);
            $table->json('operations');
            $table->json('validation')->nullable();
            $table->json('result')->nullable();
            $table->longText('before_snapshot')->nullable();
            $table->longText('after_snapshot')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_change_sets');
    }
};
