<?php

namespace App\Console\Commands;

use App\Models\WorldTemplate;
use Illuminate\Console\Command;

class RegisterRetroLocalWorld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'world-templates:register-retro-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register the existing local engine database without creating or migrating it';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Retro Local można zarejestrować tylko w lokalnym edytorze.');

            return self::FAILURE;
        }

        $database = config('database.connections.retro_local.database');
        $username = config('database.connections.retro_local.username');

        if (! is_string($database) || trim($database) === '' || ! is_string($username) || trim($username) === '') {
            $this->error('Uzupełnij nazwę bazy i użytkownika połączenia retro_local w config/database.php.');

            return self::FAILURE;
        }

        $existing = WorldTemplate::query()->where('slug', 'retro_local')->first();

        if ($existing !== null && ($existing->connection_name !== 'retro_local' || $existing->is_production)) {
            $this->error('Slug retro_local jest już używany przez inne połączenie.');

            return self::FAILURE;
        }

        WorldTemplate::query()->updateOrCreate(['slug' => 'retro_local'], [
            'name' => 'Retro Local',
            'connection_name' => 'retro_local',
            'remote_database_server' => 'local',
            'database_name' => $database,
            'is_active' => true,
            'is_visible' => true,
            'is_production' => false,
            'base_template_id' => null,
        ]);

        $this->info('Zarejestrowano Retro Local. Baza silnika nie została zmieniona.');

        return self::SUCCESS;
    }
}
