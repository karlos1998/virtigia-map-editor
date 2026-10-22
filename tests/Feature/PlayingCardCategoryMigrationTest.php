<?php

namespace Tests\Feature;

use App\Enums\BaseItemCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlayingCardCategoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_extension_preserves_existing_items_and_accepts_cards(): void
    {
        Schema::create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->enum('category', ['neutrals'])->nullable();
        });
        DB::table('base_items')->insert(['category' => 'neutrals']);
        $migration = require database_path('migrations/remote/2026_10_21_175249_add_playing_cards_category_to_base_items_table.php');

        try {
            $migration->up();
            DB::table('base_items')->insert(['category' => BaseItemCategory::PLAYING_CARDS->value]);
            $migration->down();

            $this->assertDatabaseHas('base_items', ['category' => 'neutrals']);
            $this->assertDatabaseHas('base_items', ['category' => 'playingCards']);
        } finally {
            Schema::dropIfExists('base_items');
        }
    }
}
