<?php

namespace Tests\Feature;

use App\Services\PlayingCardArtworkService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlayingCardArtworkTest extends TestCase
{
    private function card(int $strength, array $rows): array
    {
        return ['definitionId' => 'retro-scout', 'kind' => 'unit', 'strength' => $strength, 'rows' => $rows];
    }

    public function test_strength_zero_uses_starting_outfit_and_generation_is_repeatable(): void
    {
        $service = app(PlayingCardArtworkService::class);
        $assets = ['outfits' => [
            ['profession' => 'w', 'level' => 0, 'type' => 'noob', 'src' => 'img/outfits/start.gif'],
            ['profession' => 'w', 'level' => 0, 'type' => 'crimson', 'src' => 'img/outfits/private.gif'],
        ], 'backgrounds' => [['src' => 'https://portal.test/fback/common.png']]];
        $result = $service->generate($this->card(0, ['close']), $assets);

        $this->assertSame('img/outfits/start.gif', $result['outfitSrc']);
        $this->assertSame($result, $service->generate($this->card(0, ['close']), $assets));
        $this->assertSame(0, $result['frameX']);
        $this->assertSame(0, $result['frameY']);
    }

    public function test_rows_map_to_professions_and_strength_maps_to_outfit_level(): void
    {
        $assets = ['outfits' => [
            ['profession' => 'w', 'level' => 20, 'type' => 'standard', 'src' => 'img/outfits/warrior.png'],
            ['profession' => 'm', 'level' => 20, 'type' => 'standard', 'src' => 'img/outfits/mage.png'],
            ['profession' => 'h', 'level' => 20, 'type' => 'standard', 'src' => 'img/outfits/hunter.png'],
            ['profession' => 'm', 'level' => 30, 'type' => 'standard', 'src' => 'img/outfits/wrong-level.png'],
        ], 'backgrounds' => [['src' => 'https://portal.test/fback/common.png']]];
        $service = app(PlayingCardArtworkService::class);
        foreach (['close' => 'warrior', 'ranged' => 'mage', 'siege' => 'hunter'] as $row => $expected) {
            $result = $service->generate($this->card(2, [$row]), $assets);
            $this->assertSame('img/outfits/'.$expected.'.png', $result['outfitSrc']);
        }
        $agile = $service->generate($this->card(2, ['close', 'ranged']), $assets);
        $this->assertContains($agile['outfitSrc'], ['img/outfits/warrior.png', 'img/outfits/mage.png']);
    }

    public function test_missing_exact_level_uses_nearest_standard_outfit_of_an_allowed_profession(): void
    {
        $assets = ['outfits' => [
            ['profession' => 'w', 'level' => 100, 'type' => 'standard', 'src' => 'img/outfits/far.png'],
            ['profession' => 'b', 'level' => 140, 'type' => 'standard', 'src' => 'img/outfits/nearest.png'],
            ['profession' => 'm', 'level' => 150, 'type' => 'standard', 'src' => 'img/outfits/wrong-profession.png'],
            ['profession' => 'w', 'level' => 150, 'type' => 'crimson', 'src' => 'img/outfits/brotherhood.png'],
        ], 'backgrounds' => [['src' => 'img/fback/common.png']]];
        $card = $this->card(15, ['close']);
        $service = app(PlayingCardArtworkService::class);
        $result = $service->generate($card, $assets);

        $this->assertSame('img/outfits/nearest.png', $result['outfitSrc']);
        $this->assertSame('img/fback/common.png', $result['backgroundSrc']);
        $this->assertSame($result, $service->generate($card, $assets));
        $this->assertSame(15, $card['strength']);
    }

    public function test_equidistant_levels_choose_the_lower_level(): void
    {
        $assets = ['outfits' => [
            ['profession' => 'w', 'level' => 160, 'type' => 'standard', 'src' => 'img/outfits/higher.png'],
            ['profession' => 'p', 'level' => 140, 'type' => 'standard', 'src' => 'img/outfits/lower.png'],
        ], 'backgrounds' => [['src' => 'img/fback/common.png']]];

        $this->assertSame('img/outfits/lower.png', app(PlayingCardArtworkService::class)->generate($this->card(15, ['close']), $assets)['outfitSrc']);
    }

    public function test_matching_level_bracket_takes_priority_over_fallback(): void
    {
        $assets = ['outfits' => [
            ['profession' => 'w', 'level' => 149, 'type' => 'standard', 'src' => 'img/outfits/fallback.png'],
            ['profession' => 'p', 'level' => 159, 'type' => 'standard', 'src' => 'img/outfits/exact-bracket.png'],
        ], 'backgrounds' => [['src' => 'img/fback/common.png']]];

        $this->assertSame('img/outfits/exact-bracket.png', app(PlayingCardArtworkService::class)->generate($this->card(15, ['close']), $assets)['outfitSrc']);
    }

    public function test_zero_strength_does_not_fall_back_to_a_non_starting_outfit(): void
    {
        $this->expectException(ValidationException::class);
        app(PlayingCardArtworkService::class)->generate($this->card(0, ['close']), [
            'outfits' => [['profession' => 'w', 'level' => 10, 'type' => 'standard', 'src' => 'img/outfits/standard.png']],
            'backgrounds' => [['src' => 'img/fback/common.png']],
        ]);
    }

    public function test_missing_matching_artwork_has_a_clear_validation_error(): void
    {
        $this->expectException(ValidationException::class);
        app(PlayingCardArtworkService::class)->generate($this->card(10, ['close']), [
            'outfits' => [], 'backgrounds' => [['src' => 'https://portal.test/fback/common.png']],
        ]);
    }

    public function test_catalog_uses_the_configured_portal_endpoint(): void
    {
        config()->set('services.playing_card_assets.url', 'https://portal.test/api/playing-card-assets');
        Http::preventStrayRequests();
        Http::fake(['https://portal.test/*' => Http::response(['outfits' => [], 'backgrounds' => []])]);

        $this->assertSame(['outfits' => [], 'backgrounds' => []], app(PlayingCardArtworkService::class)->catalog());
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://portal.test/api/playing-card-assets');
    }
}
