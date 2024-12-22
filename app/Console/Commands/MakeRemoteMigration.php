<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeRemoteMigration extends Command
{
    protected $signature = 'make:migration:remote {name}';
    protected $description = 'Create a new migration in the remote folder';

    public function handle()
    {
        $name = $this->argument('name');

        $this->call('make:migration', [
            'name' => $name,
            '--path' => 'database/migrations/remote',
        ]);

        $this->info("Migration {$name} created successfully in remote folder.");
    }
}
