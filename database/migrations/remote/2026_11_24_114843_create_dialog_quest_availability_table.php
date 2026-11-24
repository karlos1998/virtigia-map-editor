<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialog_quest_availability', function (Blueprint $table): void {
            $table->foreignId('dialog_id')->primary()->constrained('dialogs')->cascadeOnDelete();
            $table->char('source_hash', 64);
            $table->unsignedSmallInteger('format_version');
            $table->json('requirements')->nullable();
            $table->string('fallback_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialog_quest_availability');
    }
};
