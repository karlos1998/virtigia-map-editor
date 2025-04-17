<?php

namespace App\Console\Commands;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Jobs\CombineNpcsIntoGroupsJob;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\Npc;
use App\Models\NpcGroup;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CombineNpcsIntoGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:combine-npcs-into-groups';

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
        dispatch_sync(new CombineNpcsIntoGroupsJob());
    }
}
