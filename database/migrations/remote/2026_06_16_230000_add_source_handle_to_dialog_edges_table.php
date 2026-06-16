<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialog_edges', function (Blueprint $table) {
            $table->string('source_handle')->nullable()->after('source_node_id');
        });
    }

    public function down(): void
    {
        Schema::table('dialog_edges', function (Blueprint $table) {
            $table->dropColumn('source_handle');
        });
    }
};
