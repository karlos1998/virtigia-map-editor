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
        Schema::table('dialog_edges', function (Blueprint $table) {
            $table->foreignId('source_node_id')
                ->nullable()
                ->after('source_dialog_id')
                ->constrained('dialog_nodes');
        });

        $startNodeIdsByDialog = DB::table('dialog_nodes')
            ->where('type', 'start')
            ->pluck('id', 'source_dialog_id');

        foreach ($startNodeIdsByDialog as $dialogId => $startNodeId) {
            DB::table('dialog_edges')
                ->where('source_dialog_id', $dialogId)
                ->whereNull('source_option_id')
                ->whereNull('source_node_id')
                ->update(['source_node_id' => $startNodeId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialog_edges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_node_id');
        });
    }
};
