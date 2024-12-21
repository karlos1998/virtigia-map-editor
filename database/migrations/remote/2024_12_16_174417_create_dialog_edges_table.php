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
        Schema::create('dialog_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_dialog_id')->constrained('dialogs');
            $table->foreignId('source_option_id')->nullable()->constrained('dialog_node_options');
            $table->foreignId('target_node_id')->constrained('dialog_nodes');
            $table->json('rules')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialog_node_connections');
    }
};
