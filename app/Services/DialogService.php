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
}
