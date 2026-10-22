<?php

namespace App\Console\Commands;

use App\Enums\BaseItemCategory;
use App\Models\BaseItem;
use App\Services\PlayingCardArtworkService;
use App\Services\PlayingCardThumbnailService;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportRetroPlayingCards extends Command
{
    protected $signature = 'card-game:import-retro {--dry-run : Validate the catalog and artwork without creating items}';

    protected $description = 'Create missing Retro playing-card templates only in the registered Retro Local world';

    public function handle(WorldTemplateConnectionResolver $worlds, PlayingCardArtworkService $artwork, PlayingCardThumbnailService $thumbnails): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Import jest dostępny wyłącznie lokalnie.');

            return self::FAILURE;
        }
        $world = $worlds->resolve('retro_local');
        if ($world === null || $world->connection_name !== 'retro_local') {
            $this->error('Najpierw skonfiguruj i zarejestruj Retro Local.');

            return self::FAILURE;
        }
        $worlds->registerTemplateConnection($world);
        $catalog = json_decode(file_get_contents(resource_path('retro-playing-cards.json')), true, flags: JSON_THROW_ON_ERROR);
        $assets = $artwork->catalog();
        $this->line('Katalog S3: '.count($assets['outfits']).' outfitów z ['.config('assets.dirs.outfits').']; '.count($assets['backgrounds']).' teł z ['.config('services.playing_card_assets.background_directory').'].');

        $existing = BaseItem::on('retro_local')->withTrashed()->where('category', BaseItemCategory::PLAYING_CARDS)->get(['id', 'attributes'])
            ->mapWithKeys(fn (BaseItem $item): array => [(string) data_get($item->attributes, 'playingCard.definitionId', '') => $item->id]);
        $planned = [];
        $errors = [];
        foreach ($catalog as $entry) {
            if ($existing->has($entry['card']['definitionId'])) {
                continue;
            }
            try {
                $entry['card']['artwork'] = $artwork->generate($entry['card'], $assets);
            } catch (ValidationException $exception) {
                $errors[$entry['card']['definitionId']] = array_map(
                    fn (string $message): string => $entry['label'].': '.$message,
                    Arr::flatten($exception->errors()),
                );

                continue;
            }
            $planned[] = $entry;
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
        $this->info('Nowych definicji: '.count($planned).'. Istniejące karty zachowują własne nazwy i grafiki.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }
        $connection = (new BaseItem)->setConnection('retro_local')->getConnection();
        $connection->transaction(function () use ($planned, $thumbnails): void {
            foreach ($planned as $entry) {
                $item = BaseItem::on('retro_local')->create([
                    'name' => Str::limit($entry['label'], 50, ''),
                    'src' => '', 'stats' => '', 'cl' => 0, 'pr' => 0,
                    'category' => BaseItemCategory::PLAYING_CARDS,
                    'rarity' => 'common', 'currency' => 'gold', 'price' => 0,
                    'edited_manually' => true,
                    'attributes' => ['playingCard' => $entry['card'], 'description' => $entry['description'] ?? ''],
                ]);
                $thumbnails->save($item, $entry['card']);
            }
        });

        return self::SUCCESS;
    }
}
