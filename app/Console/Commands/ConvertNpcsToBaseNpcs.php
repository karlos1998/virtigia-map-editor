<?php

namespace App\Console\Commands;

use App\Models\BaseNpc;
use App\Models\DynamicModel;
use App\Models\Npc;
use Illuminate\Console\Command;

class ConvertNpcsToBaseNpcs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:convert-npcs-to-base-npcs';

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
        DynamicModel::setGlobalConnection('retro');

        Npc::chunk(100, function($chunk){
            foreach($chunk as $npc){
                $baseNpc = BaseNpc::firstOrCreate([
                    'name' => $npc->name ,
                    'src' => $npc->src ,
                    'lvl' => $npc->lvl ,
                    'type' => $npc->type ,
                    'wt' => $npc->wt ,
                ], []);

                $npc->base()->associate($baseNpc)->save();

                try {
                    $npc->locations()->firstOrCreate([
                        'map_id' => $npc->map_id,
                        'x' => $npc->x,
                        'y' => $npc->y,
                    ], []);
                } catch (\Illuminate\Database\QueryException $exception) {
                    echo "\n Nie udało się dodac lokalizacji moba $npc->name o id $npc->id";
                }
            }
        });
    }
}
