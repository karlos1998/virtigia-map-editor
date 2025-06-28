<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class WorldInfoController extends Controller
{
    /**
     * Display advanced information about the current world.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $migrationFiles = File::files(database_path('migrations/remote'));
        $migrationFileNames = [];

        foreach ($migrationFiles as $file) {
            $migrationFileNames[] = pathinfo($file, PATHINFO_FILENAME);
        }

        $executedMigrations = DB::connection(session('world'))->table('migrations')
            ->select('migration', 'batch')
            ->get()
            ->keyBy('migration')
            ->toArray();

        $migrations = [];
        foreach ($migrationFileNames as $migration) {
            $isExecuted = array_key_exists($migration, $executedMigrations);
            $migrations[] = [
                'name' => $migration,
                'executed' => $isExecuted,
                'batch' => $isExecuted ? $executedMigrations[$migration]->batch : null,
            ];
        }

        // Sort migrations by batch (executed first, then by batch number)
        usort($migrations, function($a, $b) {
            // Put executed migrations first
            if ($a['executed'] && !$b['executed']) return -1;
            if (!$a['executed'] && $b['executed']) return 1;

            // If both are executed, sort by batch
            if ($a['executed'] && $b['executed']) {
                if ($a['batch'] != $b['batch']) {
                    return $a['batch'] - $b['batch'];
                }
            }

            // If batches are the same or both not executed, sort by name
            return strcmp($a['name'], $b['name']);
        });

        return Inertia::render('World/Info', [
            'migrations' => $migrations,
            'worldName' => session('world'),
        ]);
    }
}
