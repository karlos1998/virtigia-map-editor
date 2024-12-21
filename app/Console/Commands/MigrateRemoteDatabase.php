<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateRemoteDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:remote {connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate remote database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //  ./vendor/bin/sail php artisan db:seed --class=DialogSeeder --database=retro


        $connection = $this->argument('connection');

        $this->call('migrate', [
            '--database' => $connection,
            '--path' => 'database/migrations/remote',
        ]);

        $this->info('Migrations for the retro database executed successfully.');
    }
}
