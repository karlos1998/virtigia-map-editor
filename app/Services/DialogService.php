<?php

namespace App\Services;

use App\Http\Resources\DialogResource;
use App\Models\Dialog;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
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
            /**
             * @var DialogNode $sourceNode
             */
            $sourceNode = $dialog->nodes()->find($data['sourceNodeId']);

            /**
             * @var DialogNodeOption $sourceOption
             */
            $sourceOption = $sourceNode->options()->find($data['sourceOptionId']);

            if ($sourceOption->edges()->count() >= 1)
            {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'message' => 'Opcja może mieć NA TEN MOMENT tylko jedno połączenie',
                ]);
            }

            $edge->sourceOption()->associate($sourceOption);
        } else {
            if ($dialog->edges()->whereNull('source_option_id')->count() >= 1)
            {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'message' => 'Node wejściowy może mieć NA TEN MOMENT tylko jedno połączenie',
                ]);
            }

            if($targetNode->type != 'special')
            {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'message' => 'Node wejściowy może być połączony tylko z normalnymi dialogami (nie sklepami czy teleportacją)',
                ]);
            }
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

    public function updateNode(Dialog $dialog, DialogNode $dialogNode, array $validated)
    {
        $dialogNode->update($validated);
    }

    public function updateOption(Dialog $dialog, DialogNode $dialogNode, DialogNodeOption $dialogNodeOption, mixed $validated): DialogNodeOption
    {
        $dialogNodeOption->update($validated);
        return $dialogNodeOption;
    }

    public function destroyOption(Dialog $dialog, DialogNode $dialogNode, DialogNodeOption $dialogNodeOption)
    {
        $dialogNodeOption->delete();
    }

    public function destroyNode(Dialog $dialog, DialogNode $dialogNode)
    {
        if(!$dialogNode->dialog()->is($dialog)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if($dialogNode->type == 'special' && $dialog->nodes()->where('type', 'special')->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć jedynej kwesti dialogowej',
            ]);
        }

        $dialogNode->options()->delete();
        $dialogNode->delete();
    }

    public function destroyEdge(Dialog $dialog, \App\Models\DialogEdge $dialogEdge)
    {
        if(!$dialogEdge->sourceDialog()->is($dialog)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Błąd niespodzianka :)',
            ]);
        }

        if($dialogEdge->source_option_id === null && $dialog->edges()->whereNull('source_option_id')->count() <= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'message' => 'Nie możesz usunąć jedynego połączenia ze startowym dialogiem',
            ]);
        }

        $dialogEdge->delete();
    }
}
