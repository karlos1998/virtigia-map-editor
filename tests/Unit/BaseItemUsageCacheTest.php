<?php

namespace Tests\Unit;

use App\Services\BaseItemUsageViewService;
use PHPUnit\Framework\TestCase;

class BaseItemUsageCacheTest extends TestCase
{
    public function test_it_extracts_item_ids_from_nested_dialog_payloads(): void
    {
        $service = new BaseItemUsageViewService;

        $payload = [
            'items' => ['value' => [15, '27']],
            'nested' => [
                'addItems' => ['value' => [33, ['44']]],
                'other' => [
                    'items' => ['value' => 15],
                ],
            ],
        ];

        $itemIds = $service->extractItemIdsFromPayload($payload);

        self::assertSame([15, 27, 33, 44], $itemIds);
    }

    public function test_it_formats_location_labels_for_cached_sources(): void
    {
        $service = new BaseItemUsageViewService;

        self::assertSame(
            'Model Shop (12) x: 45, y: 67',
            $service->formatLocationLabel('Model Shop', 12, 45, 67)
        );
    }
}
