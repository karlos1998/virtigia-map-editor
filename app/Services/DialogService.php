<?php

namespace App\Services;

use App\Http\Resources\DialogResource;
use App\Models\Dialog;
use App\Models\DialogNode;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

class DialogService extends BaseService
{

    public function __construct(private readonly Dialog $dialogModel)
    {
    }

    public function getAll()
    {
        return $this->fetchData(
            DialogResource::class,
            $this->dialogModel->with(['npcs', 'npcs.locations']),
        );
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

    public function updateAction(Dialog $dialog, DialogNode $dialogNode, array $data): DialogNode
    {
        $dialogNode->update([
            'action_data' => $data,
        ]);

        return $dialogNode;
    }

    public function store(array $validated)
    {
        $dialog = Dialog::create([
            'name' => $validated['name'],
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
            'content' => 'Oto przykładowa kwestia dialogowa'
        ]);
        $node->options()->create(['label' => 'Zakończ rozmowę']);

        $dialog
            ->edges()->make()
            ->targetNode()->associate($node)
            ->save();

        return $dialog;
    }
}
