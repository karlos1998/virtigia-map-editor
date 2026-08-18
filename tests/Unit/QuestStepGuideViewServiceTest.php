<?php

namespace Tests\Unit;

use App\Services\QuestStepGuideViewService;
use Illuminate\Support\Collection;
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

    public function test_it_describes_consumed_honor_points_rule(): void
    {
        $service = new QuestStepGuideViewService;

        $descriptions = $service->describeRules(
            'retro',
            ['honorPoints' => ['value' => 50, 'consume' => true]],
            new Collection,
            new Collection,
            new Collection,
            new Collection,
            new Collection,
        );

        self::assertSame([
            [
                'type' => 'honor_points',
                'text' => 'Wydaj 50 punktów honoru',
            ],
        ], $descriptions);
    }
}
