<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table): void {
            $table->boolean('is_city')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table): void {
            $table->dropIndex(['is_city']);
            $table->dropColumn('is_city');
        });
    }
};
