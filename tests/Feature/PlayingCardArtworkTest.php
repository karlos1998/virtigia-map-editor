<?php

namespace Tests\Feature;

use App\Services\PlayingCardArtworkService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

    public function test_catalog_reads_real_path_conventions_from_s3_without_http(): void
    {
        Storage::fake('s3');
        Http::preventStrayRequests();
        foreach (['img/outfits/noob/pm.gif', 'img/outfits/noob/hk.gif', 'img/outfits/woj/20/m_a.gif',
            'img/outfits/mage/30/f_b.gif', 'img/outfits/h/40/m/new.png', 'img/outfits/p/50/f/new.png',
            'img/outfits/bd/60/o_shared.gif', 'img/outfits/trop/70/m_x.webp', 'img/outfits/crimson/pm.gif',
            'img/outfits/custom/unknown.png', 'img/outfits/w/not-a-level/image.png', 'img/outfits/w/20/info.txt',
            'img/fback/common.png'] as $path) {
            Storage::disk('s3')->put($path, 'test-image');
        }

        $catalog = app(PlayingCardArtworkService::class)->catalog();

        $this->assertCount(8, $catalog['outfits']);
        $outfits = array_column($catalog['outfits'], null, 'src');
        foreach (['woj/20/m_a.gif' => ['w', 20], 'mage/30/f_b.gif' => ['m', 30],
            'h/40/m/new.png' => ['h', 40], 'p/50/f/new.png' => ['p', 50],
            'bd/60/o_shared.gif' => ['b', 60], 'trop/70/m_x.webp' => ['t', 70]] as $path => [$profession, $level]) {
            $this->assertSame($profession, $outfits['img/outfits/'.$path]['profession']);
            $this->assertSame($level, $outfits['img/outfits/'.$path]['level']);
            $this->assertSame('standard', $outfits['img/outfits/'.$path]['type']);
        }
        $this->assertSame(['profession' => 'p', 'level' => 0, 'type' => 'noob', 'src' => 'img/outfits/noob/pm.gif'], $outfits['img/outfits/noob/pm.gif']);
        $this->assertSame(['src' => 'img/fback/common.png'], $catalog['backgrounds'][0]);
        Http::assertNothingSent();
    }

    public function test_backgrounds_are_listed_from_the_configured_s3_directory(): void
    {
        Storage::fake('s3');
        Http::preventStrayRequests();
        config()->set('services.playing_card_assets.excluded_backgrounds', ['brotherhood.png']);
        config()->set('services.playing_card_assets.excluded_outfits', ['img/outfits/w/10/excluded.gif']);
        foreach (['img/fback/z.webp', 'img/fback/a.PNG', 'img/fback/b.gif', 'img/fback/brotherhood.png',
            'img/fback/readme.txt', 'img/outfits/w/10/standard.gif', 'img/outfits/w/10/excluded.gif',
            'img/fback/private/hidden.png'] as $path) {
            Storage::disk('s3')->put($path, 'test-file');
        }

        $catalog = app(PlayingCardArtworkService::class)->catalog();

        $this->assertSame([['src' => 'img/fback/a.PNG'], ['src' => 'img/fback/b.gif'], ['src' => 'img/fback/z.webp']], $catalog['backgrounds']);
        $this->assertCount(1, $catalog['outfits']);
        Http::assertNothingSent();
    }

    public function test_empty_s3_folder_reports_the_actual_source(): void
    {
        Storage::fake('s3');
        Http::preventStrayRequests();
        Storage::disk('s3')->put('img/outfits/w/10/standard.gif', 'test-image');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Brak obrazów do kart w folderze [img/fback] na dysku [s3].');
        app(PlayingCardArtworkService::class)->catalog();
    }

    public function test_unrecognized_outfit_paths_do_not_guess_profession_or_level(): void
    {
        Storage::fake('s3');
        Http::preventStrayRequests();
        Storage::disk('s3')->put('img/outfits/unknown.gif', 'test-image');
        Storage::disk('s3')->put('img/fback/common.png', 'test-background');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Brak rozpoznanych outfitów');
        app(PlayingCardArtworkService::class)->catalog();
    }
}
