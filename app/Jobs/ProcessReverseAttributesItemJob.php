<?php

namespace App\Jobs;

use App\Models\BaseItem;
use App\Models\DynamicModel;
use App\Services\ApiAttributePointService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessReverseAttributesItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // 1 minute timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $baseItemId
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(ApiAttributePointService $apiService): void
    {
        DynamicModel::setGlobalConnection('retro');

        $baseItem = BaseItem::find($this->baseItemId);

        if (!$baseItem) {
            Log::warning("BaseItem with ID {$this->baseItemId} not found");
            return;
        }

        // Skip if already processed
        if ($baseItem->reverse_attributes !== null) {
            Log::info("BaseItem {$this->baseItemId} already has reverse attributes");
            return;
        }

        // Skip if doesn't meet criteria
        if ($baseItem->attribute_points !== null ||
            $baseItem->manual_attribute_points !== null ||
            $baseItem->attributes === null) {
            Log::info("BaseItem {$this->baseItemId} doesn't meet processing criteria");
            return;
        }

        try {
            // Extract parameters from base item
            $lvl = $baseItem->attributes['needLevel'] ?? 1;
            $category = $baseItem->category?->value ?? 'weapon';
            $itemProfessions = $baseItem->attributes['needProfessions'] ?? [];
            $rarity = $baseItem->rarity ?? 'common';

            // Extract scaled attributes (remove non-attribute fields)
            $scaledAttributes = $baseItem->attributes;

            // Call the reverse scaling API
            $reverseAttributes = $apiService->getReverseScaleAttributes(
                $lvl,
                $category,
                $itemProfessions,
                $rarity,
                $scaledAttributes
            );

            // Save the result
            $baseItem->update([
                'reverse_attributes' => $reverseAttributes
            ]);

            Log::info("Successfully processed reverse attributes for BaseItem {$this->baseItemId}");

        } catch (\Exception $e) {
            Log::error("Failed to process reverse attributes for BaseItem {$this->baseItemId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }
}
