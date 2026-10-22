<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
            if ($exception instanceof ValidationException) {
                Log::warning('Retro card import validation failed', ['errors' => $exception->errors()]);
                $this->error('Import kart został zatrzymany przez walidację:');
                foreach (Arr::flatten($exception->errors()) as $message) {
                    $this->line(' - '.$message);
                }
            } elseif ($exception instanceof QueryException && in_array((int) ($exception->errorInfo[1] ?? 0), [1142, 1143, 1227], true)) {
                $username = (string) config('database.connections.retro_local.username');
                $this->error("MariaDB odrzuciła operację: użytkownik [{$username}] nie ma wymaganych uprawnień.");
                $this->line('Migracja wymaga ALTER, a import INSERT i UPDATE. Ustaw konto z prawem zapisu w połączeniu retro_local w config/database.php.');
            } else {
                $this->error('Przygotowanie kart zostało przerwane. Szczegóły błędu znajdziesz w logu aplikacji.');
            }

            return self::FAILURE;
        }

        $this->info('Gotowe: katalog kart Retro jest dostępny w świecie Retro Local.');

        return self::SUCCESS;
    }
}
