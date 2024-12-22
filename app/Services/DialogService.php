<?php

namespace App\Services;

use App\Models\Dialog;
use App\Models\DialogNode;

class DialogService
{
    public function getAll()
    {

    }

    public function addNode(Dialog $dialog, array $data)
    {
        return $dialog->nodes()->create($data)->fresh();
    }

    public function moveNode(DialogNode $dialogNode, array $data)
    {
        $dialogNode->update($data);
    }

    public function addEdge(Dialog $dialog, array $data)
    {

        $targetNode = $dialog->nodes()->find($data['targetNodeId']);

        $edge = $dialog
            ->edges()->make()
            ->targetNode()->associate($targetNode);

        if(!$data['sourceNodeIsInput']) {
            $sourceNode = $dialog->nodes()->find($data['sourceNodeId']);
            $sourceOption = $sourceNode->options()->find($data['sourceOptionId']);
            $edge->sourceOption()->associate($sourceOption);
        }

        $edge->save();
        return $edge->fresh();
    }

    public function addOption(DialogNode $dialogNode)
    {
        return $dialogNode->options()->create([
            'label' => 'Treść odpowiedzi',
        ]);
    }
}
