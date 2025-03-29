<?php

namespace App\Console\Commands;

use App\Models\BaseItem;
use Illuminate\Console\Command;

class SearchableReindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:searchable-reindex';

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
        BaseItem::setGlobalConnection('retro');
        BaseItem::on('retro')->chunk(100, function ($items) {
            $items->searchable();
        });
    }
}
