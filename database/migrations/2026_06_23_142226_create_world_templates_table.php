<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('world_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('connection_name')->unique();
            $table->string('remote_database_server');
            $table->string('database_name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['remote_database_server', 'database_name']);
            $table->index(['is_active', 'is_visible']);
        });

        $now = now();

        foreach (config('world_templates.legacy_templates', []) as $slug => $template) {
            DB::table('world_templates')->insertOrIgnore([
                'name' => $template['name'],
                'slug' => $slug,
                'connection_name' => $template['connection_name'] ?? $slug,
                'remote_database_server' => $template['remote_database_server'],
                'database_name' => $template['database_name'],
                'is_active' => $template['is_active'] ?? true,
                'is_visible' => $template['is_visible'] ?? true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('world_templates');
    }
};
