<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_walkthroughs', function (Blueprint $table) {
            $table->foreignId('quest_id')->primary()->constrained('quests')->cascadeOnDelete();
            $table->unsignedSmallInteger('generator_version');
            $table->string('status', 16);
            $table->char('content_hash', 64);
            $table->json('walkthrough');
            $table->unsignedInteger('warnings_count')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_walkthroughs');
    }
};
