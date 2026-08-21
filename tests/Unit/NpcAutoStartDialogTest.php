<?php

namespace Tests\Unit;

use App\Models\Npc;
use App\Services\NpcService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class NpcAutoStartDialogTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_auto_start_dialog_defaults_are_cast_to_expected_types(): void
    {
        $npc = new Npc;

        $this->assertFalse($npc->auto_start_dialog);
        $this->assertSame(1, $npc->auto_start_dialog_range);

        $npc->fill([
            'auto_start_dialog' => 1,
            'auto_start_dialog_range' => '7',
        ]);

        $this->assertTrue($npc->auto_start_dialog);
        $this->assertSame(7, $npc->auto_start_dialog_range);
    }

    public function test_npc_can_update_auto_start_dialog_without_resending_dialog(): void
    {
        $npc = Mockery::mock(Npc::class)->makePartial();
        $npc->shouldReceive('fill')
            ->once()
            ->with(['auto_start_dialog' => true])
            ->andReturnSelf();
        $npc->shouldNotReceive('dialog');
        $npc->shouldReceive('save')->once()->andReturnTrue();

        $service = new NpcService(new Npc);

        $service->update($npc, ['auto_start_dialog' => true]);
    }

    public function test_npc_can_update_auto_start_dialog_range_without_resending_dialog(): void
    {
        $npc = Mockery::mock(Npc::class)->makePartial();
        $npc->shouldReceive('fill')
            ->once()
            ->with(['auto_start_dialog_range' => 6])
            ->andReturnSelf();
        $npc->shouldNotReceive('dialog');
        $npc->shouldReceive('save')->once()->andReturnTrue();

        $service = new NpcService(new Npc);

        $service->update($npc, ['auto_start_dialog_range' => 6]);
    }
}
