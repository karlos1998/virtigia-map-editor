<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dialog_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_group_id')->nullable()->constrained('dialog_groups');
            $table->foreignId('source_option_id')->nullable()->constrained('dialog_options');
            $table->foreignId('target_dialog_id')->constrained('dialogs');
            $table->json('rules')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialog_connections');
    }
};
