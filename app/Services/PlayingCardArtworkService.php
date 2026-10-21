<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PlayingCardArtworkService
{
    /** @return array<string, mixed> */
    public function catalog(): array
    {
        $url = config('services.playing_card_assets.url')
            ?: rtrim((string) config('services.virtigia_page.url'), '/').'/api/playing-card-assets';

        return Http::acceptJson()->connectTimeout(5)->timeout(15)->get($url)->throw()->json();
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  array{outfits: list<array{profession: string, level: int, type: string, src: string}>, backgrounds: list<array{src: string}>}  $assets
     * @return array{outfitSrc: ?string, backgroundSrc: ?string, illustrationSrc: ?string, frameX: int, frameY: int}
     */
    public function generate(array $card, array $assets): array
    {
        $outfit = null;
        $seed = (int) hexdec(substr(hash('sha256', $card['definitionId']), 0, 7));
        if ($card['kind'] === 'unit') {
            $professions = [];
            foreach ($card['rows'] as $row) {
                $professions = array_merge($professions, match ($row) {
                    'close' => ['b', 'w', 'p'],
                    'ranged' => ['m'],
                    'siege' => ['h', 't'],
                });
            }
            $strength = (int) $card['strength'];
            $eligibleOutfits = array_values(array_filter($assets['outfits'], fn (array $outfit): bool => in_array($outfit['profession'], $professions, true)
                && ($strength === 0
                    ? $outfit['type'] === 'noob' && $outfit['level'] === 0
                    : $outfit['type'] === 'standard')
            ));
            $outfits = array_values(array_filter($eligibleOutfits, fn (array $outfit): bool => $strength === 0 || intdiv($outfit['level'], 10) === $strength));
            if ($outfits === [] && $eligibleOutfits !== [] && $strength > 0) {
                usort($eligibleOutfits, fn (array $first, array $second): int => [abs($first['level'] - $strength * 10), $first['level']]
                    <=> [abs($second['level'] - $strength * 10), $second['level']]);
                $nearestLevel = $eligibleOutfits[0]['level'];
                $outfits = array_values(array_filter($eligibleOutfits, fn (array $outfit): bool => $outfit['level'] === $nearestLevel));
            }
            if ($outfits === []) {
                $levels = $strength === 0 ? '0 (startowy)' : ($strength * 10).'–'.($strength * 10 + 9);
                throw ValidationException::withMessages(['card' => 'Brak outfitu: siła '.$strength.', profesje '.implode('/', array_unique($professions)).', poziom '.$levels.'. Wybierz własną ilustrację lub uzupełnij katalog outfitów.']);
            }
            $outfit = $outfits[$seed % count($outfits)]['src'];
        }
        if ($assets['backgrounds'] === []) {
            throw ValidationException::withMessages(['card' => 'Brak ogólnodostępnych teł forum do wygenerowania karty.']);
        }

        return [
            'outfitSrc' => $outfit,
            'backgroundSrc' => $assets['backgrounds'][$seed % count($assets['backgrounds'])]['src'],
            'illustrationSrc' => null,
            'frameX' => 0,
            'frameY' => 0,
        ];
    }
}
