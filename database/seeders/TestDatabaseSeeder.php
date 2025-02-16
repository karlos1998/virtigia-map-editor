<?php

namespace Database\Seeders;

use App\Enums\BaseNpcCategory;
use App\Enums\Profession;
use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\Map;
use App\Models\Npc;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Facades\LogBatch;

class TestDatabaseSeeder extends Seeder
{

    private int $experimentalMapId;

    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $this->experimentalMapId = Map::create([
            'name' => 'Experimental - Mała z kolizjami',
            'src' => 'test/experimental.png',
            'x' => 32,
            'y' => 32,
            'col' => '0000000000000000000000000000000000000000000000000000000000000000011111111111111011111111111111101000000000000001000000000000000110000000000000010000000000000001100000000000000100000000000000011000000000000001000000000000000110000000000000010000000000000001100000000000000100000000000000011000000000000001000000000000000110000000000000010000000000000001100000000000000100000000000000011000000000000111000000000000000110000000000001010000000000000001100000000000010100000000000000011000000000000101000000000000000101111111111111010000000000000001100000000000000100000000000000010111111111111111000000000000000110000000000000000000000000000001100000000000000000000000000000011000000000000000000000000000000110000000000000000000000000000001100000000000000000000000000000011000000000000000000000000000000110000000000000000000000000000001100000000000000000000000000000011000000000000000000000000000000110000000000000000000000000000001100000000000000000000000000000011000000000000000000000000000000101111111111111111111111111111110',
            'battleground' => 'farmvillage01.jpg',
            'welcome' => null,
            'water' => '',
            'pvp' => 0,
        ])->id;


        /**
         * Base npcs
         */
        $roman = BaseNpc::create([
            'name' => 'Roman',
            'src' => 'test/roman.gif',
            'lvl' => 1,
            'category' => BaseNpcCategory::NPC,
            'profession' => Profession::w,
        ]);
        $rabbit = BaseNpc::create([
            'name' => 'Króliczek',
            'src' => 'test/rabbit.gif',
            'lvl' => 1,
            'category' => BaseNpcCategory::MOB,
            'profession' => Profession::w,
        ]);
        $tp = BaseNpc::create([
            'name' => 'Teleportacja',
            'src' => 'test/tp.gif',
            'lvl' => 50,
            'category' => BaseNpcCategory::NPC,
            'profession' => Profession::w,
        ]);

        /**
         * Dialogi
         */
        $tpDialog = $this->createTeleportationDialog();

        /**
         * Ustawione npc
         */
        $roman1 = $this->createNpc($roman, $this->experimentalMapId, 29, 4);

        $rabbit1 = $this->createNpc($rabbit, $this->experimentalMapId, 30, 30);
        $rabbit2 = $this->createNpc($rabbit, $this->experimentalMapId, 30, 28);
        $rabbit3 = $this->createNpc($rabbit, $this->experimentalMapId, 30, 26);
        $rabbit4 = $this->createNpc($rabbit, $this->experimentalMapId, 30, 24);

        $tp1 = $this->createNpc($tp, $this->experimentalMapId, 13, 4);
        $tp2 = $this->createNpc($tp, $this->experimentalMapId, 17, 4);

        $tp1->dialog()->associate($tpDialog)->save();
        $tp2->dialog()->associate($tpDialog)->save();

    }

    private function createNpc(BaseNpc $baseNpc, int $mapId, int $x, int $y): Npc {
        /**
         * @var Npc $npc
         */
        $npc = Npc::create();
        $npc->base()->associate($baseNpc);
        $npc->locations()->create(['map_id' => $mapId, 'x' => $x, 'y' => $y]);
        $npc->save();
        return $npc;
    }

    private function createTeleportationDialog()
    {
        $dialog = Dialog::create([
            'name' => 'Prosta teleportacja',
        ]);

        $defaultNode = $dialog->nodes()->create([
            'type' => 'start',
            'position' => [
                'x' => 0,
                'y' => 0,
            ]
        ]);

        $node = $dialog->nodes()->create([
            'position' => [
                'x' => 200,
                'y' => 100,
            ],
            'content' => 'Cześć, oto przykładowy dialog do przetestowania teleportacji'
        ]);

        $node->options()->create(['label' => 'Zakończ rozmowę']);

        $dialog
            ->edges()->make()
            ->targetNode()->associate($node)
            ->save();


        $optionTpLeft = $node->options()->create(['label' => 'Przenieś mnie do tego mniejszego sektora']);
        $optionTpRight = $node->options()->create(['label' => 'Przenieś mnie do tego większego sektora']);

        $nodeTpLeft = $dialog->nodes()->create([
            'type' => 'teleportation',
            'position' => [
                'x' => 600,
                'y' => 0,
            ],
            'action_data' => [
                'teleportation' => [
                    'mapId' => $this->experimentalMapId,
                    'x' => 13,
                    'y' => 5,
                ]
            ]
        ]);

        $nodeTpRight = $dialog->nodes()->create([
            'type' => 'teleportation',
            'position' => [
                'x' => 600,
                'y' => 300,
            ],
            'action_data' => [
                'teleportation' => [
                    'mapId' => $this->experimentalMapId,
                    'x' => 17,
                    'y' => 5,
                ]
            ]
        ]);


        $dialog
            ->edges()->make()
            ->sourceOption()->associate($optionTpLeft)
            ->targetNode()->associate($nodeTpLeft)
            ->save();


        $dialog
            ->edges()->make()
            ->sourceOption()->associate($optionTpRight)
            ->targetNode()->associate($nodeTpRight)
            ->save();

        return $dialog;
    }
}
