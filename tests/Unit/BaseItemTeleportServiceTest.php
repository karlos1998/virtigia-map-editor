<?php

namespace Tests\Unit;

use App\Services\BaseItemTeleportService;
use PHPUnit\Framework\TestCase;

class BaseItemTeleportServiceTest extends TestCase
{
    public function test_it_detects_missing_map_name_when_fourth_teleport_value_is_missing(): void
    {
        $service = new BaseItemTeleportService;

        self::assertTrue($service->teleportToHasMissingMapName([1, 10, 15]));
    }

    public function test_it_detects_missing_map_name_for_null_and_placeholder_values(): void
    {
        $service = new BaseItemTeleportService;

        self::assertTrue($service->teleportToHasMissingMapName([1, 10, 15, null]));
        self::assertTrue($service->teleportToHasMissingMapName([1, 10, 15, 'null']));
        self::assertTrue($service->teleportToHasMissingMapName([1, 10, 15, 'undefined']));
        self::assertTrue($service->teleportToHasMissingMapName([1, 10, 15, '']));
    }

    public function test_it_fills_missing_map_name_using_map_id(): void
    {
        $service = new BaseItemTeleportService;

        $attributes = [
            'teleportTo' => [1, 10, 15],
        ];

        self::assertSame(
            [
                'teleportTo' => [1, 10, 15, 'Mapa Startowa'],
            ],
            $service->fillMissingTeleportMapName($attributes, [
                1 => 'Mapa Startowa',
            ])
        );
    }

    public function test_it_leaves_existing_map_name_untouched(): void
    {
        $service = new BaseItemTeleportService;

        $attributes = [
            'teleportTo' => [1, 10, 15, 'Istniejąca mapa'],
        ];

        self::assertSame($attributes, $service->fillMissingTeleportMapName($attributes, [
            1 => 'Nowa mapa',
        ]));
    }
}
