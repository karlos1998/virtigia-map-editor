<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_template_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('target_template_id')->constrained('world_templates')->restrictOnDelete();
            $table->foreignId('source_template_id')->constrained('world_templates')->restrictOnDelete();
            $table->unsignedBigInteger('requested_by');
            $table->string('status')->default('queued');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('message')->nullable();
            $table->json('manifest')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('snapshot_pruned')->default(false);
            $table->timestamps();
            $table->index(['target_template_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_template_updates');
    }
};
