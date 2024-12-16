<?php

namespace App\Services;

use App\DTO\ConnectionDto;
use App\Models\DialogConnection;

class DialogConnectionService
{
    public function connectGroupToDialog(int $groupId, int $dialogId, array $rules): DialogConnection
    {
        $connection = new DialogConnection();
        $connection->source_group_id = $groupId;
        $connection->target_dialog_id = $dialogId;
        $connection->rules = json_encode($rules);
        $connection->save();

        return $connection;
    }

    public function connectDialogOptionToDialog(int $optionId, int $dialogId, array $rules): DialogConnection
    {
        $connection = new DialogConnection();
        $connection->source_option_id = $optionId;
        $connection->target_dialog_id = $dialogId;
        $connection->rules = json_encode($rules);
        $connection->save();

        return $connection;
    }
}
