<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class SetupRetroPlayingCards extends Command
{
    protected $signature = 'card-game:setup-retro';

    protected $description = 'Register Retro Local, migrate the playing-card category and import the Retro catalog';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Przygotowanie kart Retro jest dostępne wyłącznie lokalnie.');

            return self::FAILURE;
        }

        try {
            if ($this->call('world-templates:register-retro-local', ['--no-interaction' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }

            if ($this->call('migrate', [
                '--database' => 'retro_local',
                '--path' => ['database/migrations/remote/2026_10_21_175249_add_playing_cards_category_to_base_items_table.php'],
                '--force' => true,
                '--no-interaction' => true,
            ]) !== self::SUCCESS) {
                return self::FAILURE;
            }

            if ($this->call('card-game:import-retro', ['--no-interaction' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Przygotowanie kart zostało przerwane. Szczegóły błędu znajdziesz w logu aplikacji.');

            return self::FAILURE;
        }

        $this->info('Gotowe: katalog kart Retro jest dostępny w świecie Retro Local.');

        return self::SUCCESS;
    }
}
