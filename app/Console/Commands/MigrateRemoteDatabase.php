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
    protected $signature = 'migrate:remote {connection} {action=migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations (or rollback) on remote database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->argument('connection');
        $action = $this->argument('action');

        if ($action === 'rollback') {
            $this->call('migrate:rollback', [
                '--database' => $connection,
                '--path' => 'database/migrations/remote',
            ]);

            $this->info('Rollback for remote migrations executed successfully.');
            return 0;
        }

        // default: run migrations
        $this->call('migrate', [
            '--database' => $connection,
            '--path' => 'database/migrations/remote',
        ]);

        $this->info('Migrations for the remote database executed successfully.');
        return 0;
    }
}
