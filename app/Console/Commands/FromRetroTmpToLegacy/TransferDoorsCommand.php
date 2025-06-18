<?php

namespace App\Console\Commands\FromRetroTmpToLegacy;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TransferDoorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doors:transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer doors data from retro_tmp database to legacy database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting doors data transfer...');

        try {
            // Get doors from retro_tmp database
            $doors = DB::connection('retro_tmp')
                ->table('doors')
                ->select(['mapId', 'x', 'y', 'go'])
                ->get();

            $this->info('Found ' . count($doors) . ' doors to transfer.');

            $transferred = 0;
            $updated = 0;
            $errors = 0;

            // Insert doors into legacy database
            foreach ($doors as $door) {
                // Parse the "go" field to extract go_map_id, go_x, go_y
                try {
                    $goParts = explode(',', $door->go);

                    // Ensure we have all three parts
                    $goMapId = isset($goParts[0]) ? (int)$goParts[0] : 0;
                    $goX = isset($goParts[1]) ? (int)$goParts[1] : 0;
                    $goY = isset($goParts[2]) ? (int)$goParts[2] : 0;

                    if (count($goParts) < 3) {
                        $this->warn("Door at map {$door->mapId} ({$door->x},{$door->y}) has incomplete 'go' data: {$door->go}");
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $this->warn("Error parsing 'go' field for door at map {$door->mapId} ({$door->x},{$door->y}): {$e->getMessage()}");
                    $goMapId = 0;
                    $goX = 0;
                    $goY = 0;
                    $errors++;
                }

                // Check if the door already exists
                $exists = DB::connection('legacy')
                    ->table('doors')
                    ->where('map_id', $door->mapId)
                    ->where('x', $door->x)
                    ->where('y', $door->y)
                    ->exists();

                DB::connection('legacy')
                    ->table('doors')
                    ->updateOrInsert(
                        [
                            'map_id' => $door->mapId,
                            'x' => $door->x,
                            'y' => $door->y,
                        ],
                        [
                            'go_map_id' => $goMapId,
                            'go_x' => $goX,
                            'go_y' => $goY,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                if ($exists) {
                    $updated++;
                } else {
                    $transferred++;
                }
            }

            $this->info('Doors data transfer completed successfully!');
            $this->info("Summary: {$transferred} doors transferred, {$updated} doors updated, {$errors} errors encountered.");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error transferring doors data: ' . $e->getMessage());
            return 1;
        }
    }
}
