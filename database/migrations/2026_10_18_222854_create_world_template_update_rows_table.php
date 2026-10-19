<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_template_update_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('world_template_update_id')->constrained()->cascadeOnDelete();
            $table->string('table_name', 100);
            $table->string('row_label');
            $table->string('change_type', 16)->nullable();
            $table->json('changed_columns');
            $table->longText('before_data')->nullable();
            $table->longText('after_data')->nullable();
            $table->index(['world_template_update_id', 'table_name', 'change_type'], 'template_update_rows_filter');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_template_update_rows');
    }
};
