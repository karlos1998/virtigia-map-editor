<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('world_templates', function (Blueprint $table): void {
            $table->boolean('is_production')->default(false)->index();
            $table->foreignId('base_template_id')->nullable()->constrained('world_templates')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('world_templates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('base_template_id');
            $table->dropIndex(['is_production']);
            $table->dropColumn('is_production');
        });
    }
};
