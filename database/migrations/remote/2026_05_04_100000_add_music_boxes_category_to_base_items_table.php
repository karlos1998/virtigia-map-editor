<?php

use App\Enums\BaseItemCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('base_items', function (Blueprint $table) {
            $table->enum('category', BaseItemCategory::valuesToList())->nullable()->change();
        });
    }

    public function down(): void
    {
        // intentionally left empty (safe enum extension pattern)
    }
};

