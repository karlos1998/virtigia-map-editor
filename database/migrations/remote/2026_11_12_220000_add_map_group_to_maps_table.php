<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every map belongs to a group (caves, fields, sea...); features such as background music use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->enum('map_group', [
                'no_group',
                'bad_caves',
                'caves',
                'bad_fields',
                'fields',
                'insides',
                'bad_insides',
                'sea',
                'desert',
            ])->default('no_group');
        });
    }

    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->dropColumn('map_group');
        });
    }
};
