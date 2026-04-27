<?php

use App\Enums\BaseItemCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extend enum with the new 'pouches' value.
        Schema::table('base_items', function (Blueprint $table) {
            $table->enum('category', BaseItemCategory::valuesToList())->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keeping empty for safety (same approach as previous enum-extension migrations).
    }
};
