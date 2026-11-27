<?php

namespace Tests\Feature;

use App\Models\Audio;
use App\Models\DynamicModel;
use App\Models\User;
use App\Models\WorldTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudioSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_editor_can_browse_the_first_thirty_audios_or_search_by_name(): void
    {
        $connection = config('database.default');
        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => $connection]);
        DynamicModel::setGlobalConnection($connection);
        (require database_path('migrations/remote/2026_05_04_100100_create_audios_table.php'))->up();
        Audio::query()->insert(array_map(fn (int $index): array => [
            'name' => sprintf('Dźwięk %02d', $index),
            'path' => "img/items/audio/{$index}.mp3",
        ], range(31, 1)));

        $this->actingAs(User::factory()->create(['roles' => ['game_master']]))
            ->withSession(['world' => 'retro']);

        $this->getJson(route('audios.search'))->assertOk()->assertExactJson([]);

        $this->getJson(route('audios.search', ['browse' => 1]))
            ->assertOk()
            ->assertJsonCount(30)
            ->assertJsonPath('0.name', 'Dźwięk 01')
            ->assertJsonPath('29.name', 'Dźwięk 30');

        $this->getJson(route('audios.search', ['browse' => 1, 'query' => '31']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Dźwięk 31');
    }
}
