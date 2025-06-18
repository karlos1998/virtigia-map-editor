<?php

namespace App\Console\Commands\FromRetroTmpToLegacy;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TransferMapsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maps:transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer maps data from retro_tmp database to legacy database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting maps data transfer...');

        try {
            // Get maps from retro_tmp database
            $maps = DB::connection('retro_tmp')
                ->table('maps')
                ->select(['id', 'name', 'src', 'x', 'y', 'col', 'water', 'pvp', 'battleground'])
                ->get();

            $this->info('Found ' . count($maps) . ' maps to transfer.');

            // Insert maps into legacy database
            foreach ($maps as $map) {
                DB::connection('legacy')
                    ->table('maps')
                    ->updateOrInsert(
                        ['id' => $map->id],
                        [
                            'name' => $map->name,
                            'src' => $map->src,
                            'x' => $map->x,
                            'y' => $map->y,
                            'col' => $map->col,
                            'water' => $map->water,
                            'pvp' => $map->pvp,
                            'battleground' => $map->battleground,
                        ]
                    );
            }

            $this->info('Maps data transfer completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error transferring maps data: ' . $e->getMessage());
            return 1;
        }
    }
}
