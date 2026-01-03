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
        // Modify the enum to include the new 'pets' value
        Schema::table('base_items', function (Blueprint $table) {
            $table->enum('category', BaseItemCategory::valuesToList())->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We cannot easily remove an enum value in MySQL without recreating the column
        // This migration only adds the value, so reversing it would require complex column recreation
        // For safety, we'll leave the down method empty
    }
};
