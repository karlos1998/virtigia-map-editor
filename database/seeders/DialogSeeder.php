<?php

namespace Database\Seeders;

use App\Models\Dialog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DialogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dialog = Dialog::create([]);

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
            'content' => 'Witaj wędrowcze, w czym mogę Ci pomoóc?'
        ]);

        $option = $node->options()->create(['label' => 'Chce przejść do kolejnego etapu rozmowy']);
        $optionShop = $node->options()->create(['label' => 'Pokaż mi swoje towary']);
        $node->options()->create(['label' => 'Chcę zakończyć rozmowę']);

        $dialog
            ->edges()->make()
//            ->sourceOption()->associate($option)
            ->targetNode()->associate($node)
            ->save();




        $node2 = $dialog->nodes()->create([
            'position' => [
                'x' => 650,
                'y' => 50,
            ],
            'content' => 'Oto kolejny etap dialogu'
        ]);

        $option2 = $node2->options()->create(['label' => 'Wróć do poprzedniego kroku']);
        $node2->options()->create(['label' => 'Zakończ rozmowę']);

        $dialog
            ->edges()->make()
            ->sourceOption()->associate($option)
            ->targetNode()->associate($node2)
            ->save();


        $dialog
            ->edges()->make()
            ->sourceOption()->associate($option2)
            ->targetNode()->associate($node)
            ->save();


        $nodeShop = $dialog->nodes()->create([
            'position' => [
                'x' => 600,
                'y' => 290,
            ],
            'type' => 'shop',
        ]);

        $dialog
            ->edges()->make()
            ->sourceOption()->associate($optionShop)
            ->targetNode()->associate($nodeShop)
            ->save();
    }
}
