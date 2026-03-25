<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_item_usage_views', function (Blueprint $table) {
            $table->foreignId('base_item_id')->primary()->constrained('base_items')->cascadeOnDelete();
            $table->boolean('is_in_use')->default(false)->index();
            $table->unsignedInteger('source_count')->default(0);
            $table->json('sources')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_item_usage_views');
    }
};
