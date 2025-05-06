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

        //thuzal
        $ids = [
            347, 2020, 2066, 2055, 2899, 2900, 2901, 2065, 2176, 2064,
            2063, 2056, 2163, 2171, 2172, 2166, 2168, 2169, 2170, 2164,
            2165, 2183, 2145, 2143, 2144, 2153, 2154, 2146, 2147, 3152,
            2221, 2222, 2223, 114, 1985, 1983, 2180, 1986, 1988, 2001,
            2009, 1990, 2053, 2049, 2131, 2132, 2134, 2046, 2047, 2050,
            2051, 1992, 2124, 2126, 2125, 1994, 2008, 1991, 2007, 2002,
            2432, 2434, 2431, 2435, 2433, 2760, 2052, 2357, 2355, 2356,
            2354, 2353, 1396, 1390, 1395, 1856, 1857,
        ];
        //#1047 - Baszta p.2, #1048 - Baszta p.1, #1049 - Baszta, #1054 - Przejście astronoma, #1051 - Grota Heretyków p.2,  #1050 - Grota Heretyków p.1, #1053 - Grota Heretyków p.3, #1694 - Grota Heretyków p.4, #1695 - Grota Heretyków p.5.
//        $ids = [1047, 1048, 1049, 1054, 1051, 1050, 1053, 1694, 1695];

        //stary kupiecki trakt itp
//        $ids = [2308, 2352, 2351, 2350, 2324, 2330];

        //tristam
//        $ids = [1293, 1294, 1297, 1302, 1315, 1322, 1307, 1298, 1308, 1303, 1306, 1301, 1305, 1304, 1299, 1300];

        //pajeczy las
//        $ids = [1132,1136,1138,1140,1142];

        //mahopteki
//        $ids = [1901,1902,1903,1904,1905,1906,1924,1926,1927,1928,1932,1949,1950,1952,1960,1961,1962,1963,1964,1965,1966,1967,1968,1969,1970,1971,1972,1981,1982];

        //ingotia, magradit, ceneum zachodnie
//        $ids = [1360,1361,1362,1364,1364,1365,1366,1615,1640,1641,1635,1632,1636,1637,1634,1645,1633,1649,1650,1651,1652,1648,1653,1647,1642,1643,1644,1316];

        //tuzmer i okolice
//        $ids = [
//            589, 1600, 1499, 1500, 1497, 1498, 1442, 1522, 1312, 1331, 1490, 1491, 1494, 1492, 1574, 1575, 1495, 1496, 1505,
//            1333, 1334, 1335, 1326, 1567, 1908, 1594, 1318, 1320, 1319, 1329, 1330, 1313, 1518, 1516, 1517, 1572, 1487, 1489,
//            1488, 1433, 1434, 1440, 1441, 1564, 1565, 1427, 1599, 1576, 1438, 1439, 1539, 1543, 1541, 1542, 1553, 1566, 1429,
//            1430, 1435, 1520, 1521, 630, 1369, 1371, 1191, 1181, 1184, 1182, 1372, 1373, 1203, 1379, 1385, 1224, 1382, 1187,
//            1188, 1185, 1217, 1417, 1376, 1413, 1482, 1175, 1178, 1383, 1388, 1389, 1453, 1179, 1180, 1405, 1407, 1406, 1421,
//            1423, 1422, 1262, 1604, 1605, 1606, 2484, 1277, 1278, 1280, 1279, 1281, 1337, 1338, 1339, 1340, 1341, 1344, 1343,
//            1342, 1345, 1525, 1528, 1527, 3409, 1526,
//            1154, 1213
//        ];

        //krolik i orla
//        $ids = [1745, 1800];

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
            dd('ktoras z wybranych map juz istnieje', \App\Models\Map::whereIn('id', $ids)->pluck('id'));
        }
        DynamicModel::setGlobalConnection('classic');

        $maps = \App\Models\Map::whereIn('id', $ids)->get();

        $mapsArray = [];

        foreach ($maps as $map) {
            $this->copyImageToRetroDirectory("img/locations/{$map->src}");

            $mapData = $map->toArray();

            $mapData['pvp'] = $map->pvp == 2 ? 3 : $map->pvp;
            $mapData['src'] = str_replace('classic', 'retro', $map->src);

            if (isset($mapData['welcome'])) {
//                $mapData['welcome'] = mb_convert_encoding($mapData['welcome'], 'UTF-8', 'UTF-8');
//                $mapData['welcome'] = preg_replace('/[^\x20-\x7E\xA0-\xFFąćęłńóśżźĄĆĘŁŃÓŚŻŹ]/u', '', $mapData['welcome']);
                $mapData['welcome'] = "";
            }

            $mapsArray[] = $mapData;
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
//                    if($data['type'] == 3) {
//                        $data['rank'] = BaseNpcRank::ELITE;
//                    }
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
                $data['max_lvl'] = $data['max_lvl'] ?? null;
                $data['min_lvl'] = $data['min_lvl'] ?? null;
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
