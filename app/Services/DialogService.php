<?php

namespace App\Services;

use App\DTO\DialogDto;
use App\DTO\DialogOptionDto;
use App\DTO\OptionDto;
use App\Models\Dialog;
use App\Models\DialogConnection;
use App\Models\DialogOption;

class DialogService
{
    public function createDialog(int $groupId, DialogDto $dialogDto): Dialog
    {
        $dialog = new Dialog();
        $dialog->title = $dialogDto->title;
        $dialog->content = $dialogDto->content;
        $dialog->save();

        $connection = new DialogConnection();
        $connection->source_group_id = $groupId;
        $connection->target_dialog_id = $dialog->id;
        $connection->rules = json_encode([]);
        $connection->save();

        foreach ($dialogDto->options as $optionData) {
            $optionDto = new DialogOptionDto($optionData);

            $option = new DialogOption();
            $option->dialog_id = $dialog->id;
            $option->content = $optionDto->content;
            $option->rules = json_encode($optionDto->rules);
            $option->save();

            foreach ($optionDto->targetDialogs as $targetDialogId) {
                $connection = new DialogConnection();
                $connection->source_option_id = $option->id;
                $connection->target_dialog_id = $targetDialogId;
                $connection->rules = json_encode([]);
                $connection->save();
            }
        }

        return $dialog;
    }

    public function getDialog(int $dialogId)
    {
        return Dialog::with('options')->find($dialogId);
    }

    public function deleteDialog(int $dialogId)
    {
        Dialog::destroy($dialogId);
    }

    public function updateDialog(int $dialogId, DialogDto $dialogDto)
    {
        $dialog = Dialog::find($dialogId);
        $dialog->group_id = $dialogDto->group_id;
        $dialog->title = $dialogDto->title;
        $dialog->content = $dialogDto->content;
        $dialog->save();

        $dialog->options()->delete();

        foreach ($dialogDto->options as $optionData) {
            $optionDto = new DialogOptionDto($optionData);

            $option = new DialogOption();
            $option->dialog_id = $dialog->id;
            $option->content = $optionDto->content;
            $option->rules = json_encode($optionDto->rules);
            $option->save();

            foreach ($optionDto->targetDialogs as $targetDialogId) {
                $connection = new DialogConnection();
                $connection->source_option_id = $option->id;
                $connection->target_dialog_id = $targetDialogId;
                $connection->rules = json_encode([]);
                $connection->save();
            }
        }

        return $dialog;
    }
}
