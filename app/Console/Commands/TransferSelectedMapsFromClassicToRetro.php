<?php

namespace App\Console\Commands;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Models\BaseNpc;
use App\Models\Door;
use App\Models\DynamicModel;
use App\Models\Npc;
use App\Models\NpcLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransferSelectedMapsFromClassicToRetro extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transfer-selected-maps-from-classic-to-retro';

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

        //krolik i orla
        $ids = [1745, 1800];

        //mumie przed tuzmer
//        $ids = [1159, 1974, 1223, 1346, 1347, 1167, 1357, 1356, 1622, 1621,
//            1348, 1877, 1613, 1607, 1350, 3081, 1349, 1368, 1358];


        //jakies miski podobno
//        $ids = [1733, 1735, 1734, 1736, 1130, 1193, 1204, 3530, 1730];

        //mapy z sosnowego
//        $ids = [
//            701, 1161, 1162, 1163, 1164, 1165, 1249, 1250, 1251, 1252, 1257, 1258, 1266, 1268, 1269, 1270,
//            1261, 1290, 1259, 1260, 1255, 1256, 1254, 1253, 1192, 1296, 1295, 1276, 1275, 1274, 1273,
//            1292, 1271, 1272, 1289, 1288, 1287, 1286, 1137, 1323, 1324, 1325, 1141, 1145, 1144, 1146,
//            1147, 1148, 1149, 1150, 1151, 1152, 1153
//        ];


        DynamicModel::setGlobalConnection('retro');
        if(\App\Models\Map::whereIn('id', $ids)->exists()) {
            dd('ktoras z wybranych map juz istnieje');
        }
        DynamicModel::setGlobalConnection('classic');

        $maps = \App\Models\Map::whereIn('id', $ids)->get();

        $mapsArray = [];
        foreach ($maps as $map) {
            $this->copyImageToRetroDirectory("img/locations/{$map->src}");
            $mapsArray[] = [
              ...$map->toArray(),
              'src' => str_replace('classic', 'retro', $map->src),
            ];
        }

        $npcLocations = NpcLocation::whereIn('map_id', $ids)->get();
        $npcIds = $npcLocations->pluck('npc_id')->unique();
        $npcs = Npc::whereIn('id', $npcIds)->get();
        $baseNpcIds = $npcs->pluck('base_npc_id')->unique();
        $baseNpcs = BaseNpc::whereIn('id', $baseNpcIds)->get();

        $baseNpcMapping = [];
        $npcMapping = [];

        $doors = Door::whereIn('map_id', $ids)->whereIn('go_map_id', $ids)->get();

        foreach ($baseNpcs as $baseNpc) {
            $this->copyImageToRetroDirectory("img/npc/{$baseNpc->src}");
        }

        DB::connection('retro')->transaction(function () use ($mapsArray, $baseNpcs, $npcs, $npcLocations, &$baseNpcMapping, &$npcMapping, $doors) {

            DB::connection('retro')->table('maps')->insert($mapsArray);

            foreach ($baseNpcs as $baseNpc) {
                $data = $baseNpc->toArray();
                unset($data['id']);

                //todo - dodac logike i przydzielac MOB zamiast NPC gdy lvl > 1 && type ... no wlasnie. tu jakies liczby

                if($data['type'] == 2 || $data['type'] == 3) {
                    $data['category'] = BaseNpcCategory::MOB;
                    if($data['type'] == 3) {
                        $data['rank'] = BaseNpcRank::ELITE;
                    }
                }

                $data['src'] = str_replace('classic', 'retro', $data['src']);
                $data['created_at'] = now()->toDateTimeString();
                $data['updated_at'] = now()->toDateTimeString();
                $newId = DB::connection('retro')->table('base_npcs')->insertGetId($data);
                $baseNpcMapping[$baseNpc->id] = $newId;
            }
            foreach ($npcs as $npc) {
                $data = $npc->toArray();
                $data['base_npc_id'] = $baseNpcMapping[$data['base_npc_id']] ?? $data['base_npc_id'];
                unset($data['id']);
                $data['created_at'] = now()->toDateTimeString();
                $data['updated_at'] = now()->toDateTimeString();
                $newId = DB::connection('retro')->table('npcs')->insertGetId($data);
                $npcMapping[$npc->id] = $newId;
            }
            foreach ($npcLocations as $location) {
                $data = $location->toArray();
                $data['npc_id'] = $npcMapping[$data['npc_id']] ?? $data['npc_id'];
                unset($data['id']);
                $data['created_at'] = now()->toDateTimeString();
                $data['updated_at'] = now()->toDateTimeString();
                DB::connection('retro')->table('npc_locations')->insert($data);
            }

            foreach ($doors as $door) {
                $data = $door->toArray();
                unset($data['id']);
                $data['created_at'] = now()->toDateTimeString();
                $data['updated_at'] = now()->toDateTimeString();
                DB::connection('retro')->table('doors')->insert($data);
            }
        });





//        dd($doors->count());
//        DB::connection('retro')->transaction(function () use ($doors) {
//            foreach ($doors as $door) {
//                $data = $door->toArray();
//                unset($data['id']);
//                $data['created_at'] = now()->toDateTimeString();
//                $data['updated_at'] = now()->toDateTimeString();
//                DB::connection('retro')->table('doors')->insert($data);
//            }
//        });

    }

    private function copyImageToRetroDirectory(string $originalPath)
    {
        if (!Storage::disk('s3')->exists($originalPath)) {
            $this->error("Brak pliku: {$originalPath}");
            return;
        }

        $retroPath = str_replace('classic', 'retro', $originalPath);

        if (Storage::disk('s3')->exists($retroPath)) {
            $this->info("Plik już istnieje: {$retroPath}");
            return;
        }

        Storage::disk('s3')->copy($originalPath, $retroPath);
        $this->info("Skopiowano: {$originalPath} -> {$retroPath}");
    }
}
