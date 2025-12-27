<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedSpecialAttacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:special-attacks {connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run SpecialAttackSeeder on remote database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->argument('connection');

        $this->call('db:seed', [
            '--database' => $connection,
            '--class' => 'SpecialAttackSeeder',
        ]);

        $this->info('SpecialAttackSeeder executed successfully on ' . $connection . ' database.');
        return 0;
    }
}
