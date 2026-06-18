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
        Schema::create('base_item_duplicate_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duplicate_base_item_id')->constrained('base_items')->cascadeOnDelete();
            $table->foreignId('used_base_item_id')->constrained('base_items')->cascadeOnDelete();
            $table->char('duplicate_group_hash', 40)->index();
            $table->string('normalized_name', 191)->index();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->string('rarity')->nullable()->index();
            $table->unsignedInteger('need_level')->nullable()->index();
            $table->string('duplicate_src');
            $table->string('used_src');
            $table->unsignedInteger('duplicate_usage_source_count')->default(0);
            $table->unsignedInteger('used_usage_source_count')->default(0);
            $table->timestamp('refreshed_at')->index();
            $table->timestamps();

            $table->unique(
                ['duplicate_base_item_id', 'used_base_item_id'],
                'base_item_duplicate_views_pair_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_item_duplicate_views');
    }
};
