<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dialog_node_options', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('label');
        });

        // Populate order for existing records grouped by node
        $groups = DB::table('dialog_node_options')
            ->select('id', 'node_id')
            ->orderBy('id')
            ->get()
            ->groupBy('node_id');

        foreach ($groups as $nodeId => $rows) {
            $i = 0;
            foreach ($rows as $row) {
                DB::table('dialog_node_options')->where('id', $row->id)->update(['order' => $i]);
                $i++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('dialog_node_options', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
