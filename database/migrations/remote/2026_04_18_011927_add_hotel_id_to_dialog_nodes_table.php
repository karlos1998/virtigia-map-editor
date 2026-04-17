<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialog_nodes', function (Blueprint $table): void {
            $table->foreignId('hotel_id')->nullable()->constrained('hotels');
        });
    }

    public function down(): void
    {
        Schema::table('dialog_nodes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('hotel_id');
        });
    }
};
