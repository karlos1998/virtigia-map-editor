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
        Schema::create('dialog_nodes', function (Blueprint $table) {
            $table->id();

            $table->string('type')->default('special');
            $table->json('position');//->default(json_encode(['x'=>0, 'y'=>0])); //w mysql nie dziala

            $table->text('content')->nullable();
            $table->foreignId('source_dialog_id')->constrained('dialogs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialog_nodes');
    }
};
