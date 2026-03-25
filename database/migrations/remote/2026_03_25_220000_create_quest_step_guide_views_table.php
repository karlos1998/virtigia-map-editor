<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_step_guide_views', function (Blueprint $table) {
            $table->foreignId('quest_step_id')->primary()->constrained('quest_steps')->cascadeOnDelete();
            $table->unsignedInteger('guide_count')->default(0);
            $table->json('guides')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_step_guide_views');
    }
};
