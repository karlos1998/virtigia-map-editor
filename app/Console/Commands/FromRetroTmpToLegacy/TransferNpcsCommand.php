<?php

namespace App\Console\Commands\FromRetroTmpToLegacy;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TransferNpcsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'npcs:transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer NPCs data from retro_tmp database to legacy database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting NPCs data transfer...');

        try {
            // Get NPCs from retro_tmp database
            $npcs = DB::connection('retro_tmp')
                ->table('npcs')
                ->select(['mapId', 'nick', 'src', 'x', 'y'])
                ->get();

            $this->info('Found ' . count($npcs) . ' NPCs to transfer.');

            $baseNpcsCreated = 0;
            $baseNpcsReused = 0;
            $npcsCreated = 0;
            $npcLocationsCreated = 0;
            $errors = 0;

            // Process each NPC
            foreach ($npcs as $npc) {
                try {
                    // Modify src path
                    $legacySrc = str_replace('retro/', 'legacy/', $npc->src);

                    // Begin transaction
                    DB::connection('legacy')->beginTransaction();

                    // Check if BaseNpc with the same name and src already exists
                    $baseNpc = DB::connection('legacy')
                        ->table('base_npcs')
                        ->where('name', $npc->nick)
                        ->where('src', $legacySrc)
                        ->first();

                    $baseNpcId = null;

                    if ($baseNpc) {
                        // Reuse existing BaseNpc
                        $baseNpcId = $baseNpc->id;
                        $baseNpcsReused++;
                    } else {
                        // Create new BaseNpc
                        $baseNpcId = DB::connection('legacy')
                            ->table('base_npcs')
                            ->insertGetId([
                                'name' => $npc->nick,
                                'src' => $legacySrc,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $baseNpcsCreated++;
                    }

                    // Create new Npc
                    $npcId = DB::connection('legacy')
                        ->table('npcs')
                        ->insertGetId([
                            'base_npc_id' => $baseNpcId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    $npcsCreated++;

                    // Create new NpcLocation
                    DB::connection('legacy')
                        ->table('npc_locations')
                        ->insert([
                            'npc_id' => $npcId,
                            'map_id' => $npc->mapId,
                            'x' => $npc->x,
                            'y' => $npc->y,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    $npcLocationsCreated++;

                    // Commit transaction
                    DB::connection('legacy')->commit();
                } catch (\Exception $e) {
                    // Rollback transaction on error
                    DB::connection('legacy')->rollBack();
                    $this->error('Error processing NPC ' . $npc->nick . ' at map ' . $npc->mapId . ' (' . $npc->x . ',' . $npc->y . '): ' . $e->getMessage());
                    $errors++;
                }
            }

            $this->info('NPCs data transfer completed successfully!');
            $this->info("Summary: {$baseNpcsCreated} base NPCs created, {$baseNpcsReused} base NPCs reused, {$npcsCreated} NPCs created, {$npcLocationsCreated} NPC locations created, {$errors} errors encountered.");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error transferring NPCs data: ' . $e->getMessage());
            return 1;
        }
    }
}
