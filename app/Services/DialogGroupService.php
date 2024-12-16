<?php

namespace App\Services;

use App\Models\DialogGroup;

class DialogGroupService
{
    public function createGroup(): DialogGroup
    {
        $group = new DialogGroup();
        $group->save();
        return $group;
    }

    public function getGroup(int $groupId)
    {
        return DialogGroup::find($groupId);
    }

    public function deleteGroup(int $groupId)
    {
        DialogGroup::destroy($groupId);
    }
}
