<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedRemoteDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:remote {connection} {class?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run seeders on remote database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->argument('connection');
        $class = $this->argument('class');

        $options = [
            '--database' => $connection,
        ];

        if ($class) {
            $options['--class'] = $class;
        }

        $this->call('db:seed', $options);

        $this->info('Seeders for the remote database executed successfully.');
        return 0;
    }
}
