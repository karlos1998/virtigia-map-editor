<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;

class PlayingCardArtworkService
{
    /** @return array{outfits: list<array{profession: string, level: int, type: string, src: string}>, backgrounds: list<array{src: string}>} */
    public function catalog(): array
    {
        return ['outfits' => $this->outfits(), 'backgrounds' => $this->backgrounds()];
    }

    /** @return list<array{profession: string, level: int, type: string, src: string}> */
    private function outfits(): array
    {
        $directory = trim((string) config('assets.dirs.outfits'), '/');
        $professions = ['w' => 'w', 'woj' => 'w', 'p' => 'p', 'pal' => 'p', 'b' => 'b', 'bd' => 'b',
            'm' => 'm', 'mage' => 'm', 'h' => 'h', 't' => 't', 'trop' => 't'];
        $excluded = config('services.playing_card_assets.excluded_outfits', []);
        $outfits = [];
        foreach ($this->imageFiles($directory, recursive: true) as $path) {
            if (in_array($path, $excluded, true)) {
                continue;
            }
            $parts = explode('/', substr($path, strlen($directory) + 1));
            $folder = strtolower($parts[0]);
            if ($folder === 'noob' && count($parts) === 2
                && preg_match('/^([bwhptm])[mkfo](?:[_-][^.]+)?\.(?:png|gif|jpe?g|webp)$/i', $parts[1], $matches)) {
                $outfits[] = ['profession' => strtolower($matches[1]), 'level' => 0, 'type' => 'noob', 'src' => $path];
            } elseif (isset($professions[$folder]) && count($parts) >= 3 && ctype_digit($parts[1])) {
                $outfits[] = ['profession' => $professions[$folder], 'level' => (int) $parts[1], 'type' => 'standard', 'src' => $path];
            }
        }
        if ($outfits === []) {
            throw ValidationException::withMessages(['outfits' => "Brak rozpoznanych outfitów w [{$directory}]. Oczekiwane ścieżki: profesja/poziom/plik lub noob/pm.gif."]);
        }

        return $outfits;
    }

    /** @return list<array{src: string}> */
    private function backgrounds(): array
    {
        $directory = trim((string) config('services.playing_card_assets.background_directory'), '/');
        $excluded = config('services.playing_card_assets.excluded_backgrounds', []);
        $files = array_values(array_filter($this->imageFiles($directory), fn (string $path): bool => ! in_array($path, $excluded, true) && ! in_array(basename($path), $excluded, true)
        ));
        if ($files === []) {
            throw ValidationException::withMessages(['backgrounds' => "Brak obrazów do kart w folderze [{$directory}] na dysku [s3]."]);
        }

        return array_map(fn (string $path): array => ['src' => $path], $files);
    }

    /** @return list<string> */
    private function imageFiles(string $directory, bool $recursive = false): array
    {
        try {
            $disk = Storage::disk('s3');
            $files = $recursive ? $disk->allFiles($directory) : $disk->files($directory);
        } catch (FilesystemException $exception) {
            report($exception);

            throw ValidationException::withMessages(['assets' => "Nie udało się odczytać folderu [{$directory}] na dysku [s3]. Sprawdź dostęp do S3."]);
        }
        $files = array_values(array_filter($files, fn (string $path): bool => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['png', 'gif', 'jpg', 'jpeg', 'webp'], true)
        ));
        sort($files, SORT_STRING);

        return $files;
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
