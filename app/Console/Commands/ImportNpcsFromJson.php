<?php

namespace App\Console\Commands;

use App\Models\BaseNpc;
use App\Models\DynamicModel;
use App\Models\Npc;
use App\Models\NpcLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportNpcsFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-npcs-from-json';

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

        $json = json_decode(file_get_contents(storage_path("app/npcs.json")));

        foreach($json as $jsonItem) {

            $response = Http::get('https://virtigia-assets.letscode.it/img/npc/retro/' . $jsonItem->src);
            if(!$response->successful()) {
                dd('error:', $jsonItem);
            }

            if(NpcLocation::where('map_id', $jsonItem->mapId)->where('x', $jsonItem->x)->where('y', $jsonItem->y)->exists()) {
                continue;
            }

            /**
             * @var Npc $npc
             */
            $npc = Npc::make([
                'grp' => $jsonItem->grp,
            ]);

            $baseNpc = BaseNpc::firstOrCreate([
                'name' => $jsonItem->name,
                'src' => "retro/$jsonItem->src",
                'lvl' => $jsonItem->lvl,
                'type' => -$jsonItem->type, //todo ! na razie, by bylo latwiej znaelzc
            ], []);

            $npc->base()->associate($baseNpc)->save();

            $npc->locations()->firstOrCreate([
                'map_id' => $jsonItem->mapId,
                'x' => $jsonItem->x,
                'y' => $jsonItem->y,
            ], []);
        }
    }
}
