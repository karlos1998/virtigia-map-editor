<?php

use App\Enums\BaseItemCurrency;
use App\Enums\HotelPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->enum('currency', BaseItemCurrency::valuesToList())->default(BaseItemCurrency::DRAGON_TEAR->value)->after('name');
            $table->enum('period', HotelPeriod::valuesToList())->default(HotelPeriod::MONTH->value)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['currency', 'period']);
        });
    }
};
