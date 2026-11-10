<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Polanka Thinkera" is an administration/test map, like "Siedziba MG".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('maps')->where('name', 'Polanka Thinkera')->update(['is_internal' => true]);
    }

    public function down(): void
    {
        DB::table('maps')->where('name', 'Polanka Thinkera')->update(['is_internal' => false]);
    }
};
