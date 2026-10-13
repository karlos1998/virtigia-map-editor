<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            // How a layer (type 4) is drawn relative to characters: Y_SORT, ABOVE or BELOW
            $table->string('layer_depth', 16)->default('Y_SORT')->after('draw_offset_y');
        });

        // Existing layers used to be drawn mostly over characters; keep them above and fix the few exceptions by hand
        DB::table('base_npcs')->where('type', 4)->update(['layer_depth' => 'ABOVE']);
    }

    public function down(): void
    {
        Schema::table('base_npcs', function (Blueprint $table) {
            $table->dropColumn('layer_depth');
        });
    }
};
