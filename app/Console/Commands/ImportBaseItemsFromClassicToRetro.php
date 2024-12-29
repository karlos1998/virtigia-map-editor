<?php

namespace App\Console\Commands;

use App\Models\BaseItem;
use Illuminate\Console\Command;

class ImportBaseItemsFromClassicToRetro extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-base-items-from-classic-to-retro';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Base Items from Classic database to Retro database without duplicates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        BaseItem::on('classic')
            ->chunk(100, function ($items) {
                foreach ($items as $item) {
                    BaseItem::on('retro')->updateOrCreate(
                        [
                            'name' => $item->name,
                            'stats' => $item->stats,
                            'src' => $item->src,
                            'cl' => $item->cl,
                            'pr' => $item->pr,
                        ],
                    );
                }
            });

        $this->info('Import zakończony.');
    }
}
