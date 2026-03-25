<?php

namespace Tests\Unit;

use App\Services\QuestStepGuideViewService;
use PHPUnit\Framework\TestCase;

class QuestStepGuideViewServiceTest extends TestCase
{
    public function test_it_extracts_item_ids_from_item_rules(): void
    {
        $service = new QuestStepGuideViewService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractItemIdsFromPayload');

        $result = $method->invoke($service, [
            'items' => [
                'value' => [4, 8, '12'],
                'consume' => true,
            ],
        ]);

        self::assertSame([4, 8, 12], $result);
    }
}
