<?php

namespace App\Console\Commands;

use App\Models\BaseItem;
use Illuminate\Console\Command;

class RemoveBindingAttributesFromBaseItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-binding-attributes-from-base-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove binding attributes (isPermamentlyBounded, isBindsAfterEquip, isBoundToOwner) from BaseItem attributes JSON field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to remove binding attributes from BaseItems...');

        $fieldsToRemove = [
            'isPermamentlyBounded', //zwiazany na stale
            'isBindsAfterEquip', //wiaze po zalozeniu
            'isBoundToOwner' //zwiazany z wlascicielem
        ];

        // Create a query that filters only items containing any of the binding attributes we want to remove
        $query = BaseItem::on('retro')->where(function($q) use ($fieldsToRemove) {
            foreach ($fieldsToRemove as $field) {
                $q->orWhereRaw("JSON_EXTRACT(attributes, '$.$field') = 'true'");
            }
        });

        $totalItems = $query->count();
        $modifiedItems = 0;

        $query->chunk(100, function ($items) use ($fieldsToRemove, &$modifiedItems) {
            foreach ($items as $item) {
                $attributes = $item->attributes;
                $modified = false;

                if (!is_array($attributes)) {
                    continue;
                }

                foreach ($fieldsToRemove as $field) {
                    if (isset($attributes[$field]) && $attributes[$field] === true) {
                        unset($attributes[$field]);
                        $modified = true;
                    }
                }

                if ($modified) {
                    $item->attributes = $attributes;
                    $item->save();
                    $modifiedItems++;
                    $this->line("Modified item: {$item->id} - {$item->name}");
                }
            }
        });

        $this->info("Completed! Modified $modifiedItems out of $totalItems items.");
    }
}
