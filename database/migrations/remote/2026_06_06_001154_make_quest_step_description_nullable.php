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
        Schema::table('quest_steps', function (Blueprint $table): void {
            $table->text('description')->nullable()->change();
        });

        DB::table('quest_steps')
            ->select(['id', 'description'])
            ->whereNotNull('description')
            ->orderBy('id')
            ->chunkById(500, function ($questSteps): void {
                $questStepIds = $questSteps
                    ->filter(fn ($questStep): bool => mb_strlen(trim((string) $questStep->description)) < 4)
                    ->pluck('id');

                if ($questStepIds->isEmpty()) {
                    return;
                }

                DB::table('quest_steps')
                    ->whereIn('id', $questStepIds)
                    ->update(['description' => null]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('quest_steps')
            ->whereNull('description')
            ->update(['description' => '']);

        Schema::table('quest_steps', function (Blueprint $table): void {
            $table->text('description')->nullable(false)->change();
        });
    }
};
