<?php

namespace App\Console\Commands;

use App\Models\BaseItem;
use App\Models\DynamicModel;
use Illuminate\Console\Command;

class ResetReverseAttributesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverse-attributes:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset reverse_attributes field to null for all BaseItems';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DynamicModel::setGlobalConnection('retro');

        $this->info('Resetting reverse_attributes for all BaseItems...');

        $count = BaseItem::whereNotNull('reverse_attributes')->update([
            'reverse_attributes' => null
        ]);

        $this->info("Reset {$count} BaseItems reverse_attributes to null.");

        return Command::SUCCESS;
    }
}
