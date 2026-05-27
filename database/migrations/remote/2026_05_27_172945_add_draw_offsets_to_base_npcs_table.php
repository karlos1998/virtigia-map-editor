<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->integer('draw_offset_x')->default(0)->after('wt');
            $table->integer('draw_offset_y')->default(0)->after('draw_offset_x');
        });
    }

    public function down(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->dropColumn(['draw_offset_x', 'draw_offset_y']);
        });
    }
};
