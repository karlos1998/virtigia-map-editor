<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTestDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-test-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        activity()->disableLogging();

        $this->call('migrate:fresh', [
            '--database' => 'test',
            '--path' => 'database/migrations/remote',
        ]);

        $this->call('db:seed', [
            '--class' => 'TestDatabaseSeeder',
            '--database' => 'test',
        ]);

        $this->info('Test database seeded successfully.');
    }
}
