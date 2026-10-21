<?php

namespace Tests\Feature;

use App\Console\Commands\SetupRetroPlayingCards;
use Illuminate\Console\Command;
use Mockery;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class SetupRetroPlayingCardsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.retro_local', config('database.connections.'.config('database.default')));
    }

    public function test_setup_targets_only_the_local_card_migration_then_imports_the_catalog(): void
    {
        $command = Mockery::mock(SetupRetroPlayingCards::class)->makePartial();
        $command->__construct();
        $command->shouldReceive('call')->once()->with('world-templates:register-retro-local', ['--no-interaction' => true])->ordered()->andReturn(Command::SUCCESS);
        $command->shouldReceive('call')->once()->with('migrate', [
            '--database' => 'retro_local',
            '--path' => ['database/migrations/remote/2026_10_21_175249_add_playing_cards_category_to_base_items_table.php'],
            '--force' => true,
            '--no-interaction' => true,
        ])->ordered()->andReturn(Command::SUCCESS);
        $command->shouldReceive('call')->once()->with('card-game:import-retro', ['--no-interaction' => true])->ordered()->andReturn(Command::SUCCESS);
        $command->setLaravel($this->app);
        $output = new BufferedOutput;

        $this->assertSame(Command::SUCCESS, $command->run(new ArrayInput([]), $output));
        $this->assertStringContainsString('Gotowe:', $output->fetch());
    }

    public function test_registration_failure_does_not_touch_the_engine_schema(): void
    {
        $command = Mockery::mock(SetupRetroPlayingCards::class)->makePartial();
        $command->__construct();
        $command->shouldReceive('call')->once()->with('world-templates:register-retro-local', ['--no-interaction' => true])->andReturn(Command::FAILURE);
        $command->setLaravel($this->app);
        $output = new BufferedOutput;

        $this->assertSame(Command::FAILURE, $command->run(new ArrayInput([]), $output));
        $this->assertStringNotContainsString('Gotowe:', $output->fetch());
    }

    public function test_migration_failure_prevents_import(): void
    {
        $command = Mockery::mock(SetupRetroPlayingCards::class)->makePartial();
        $command->__construct();
        $command->shouldReceive('call')->once()->with('world-templates:register-retro-local', ['--no-interaction' => true])->ordered()->andReturn(Command::SUCCESS);
        $command->shouldReceive('call')->once()->with('migrate', Mockery::type('array'))->ordered()->andReturn(Command::FAILURE);
        $command->setLaravel($this->app);
        $output = new BufferedOutput;

        $this->assertSame(Command::FAILURE, $command->run(new ArrayInput([]), $output));
        $this->assertStringNotContainsString('Gotowe:', $output->fetch());
    }

    public function test_connection_exception_stops_setup_and_reports_failure_without_internal_details(): void
    {
        $command = Mockery::mock(SetupRetroPlayingCards::class)->makePartial();
        $command->__construct();
        $command->shouldReceive('call')->once()->with('world-templates:register-retro-local', ['--no-interaction' => true])->ordered()->andReturn(Command::SUCCESS);
        $command->shouldReceive('call')->once()->with('migrate', Mockery::type('array'))->ordered()->andThrow(new RuntimeException('internal connection details'));
        $command->setLaravel($this->app);
        $output = new BufferedOutput;

        $this->assertSame(Command::FAILURE, $command->run(new ArrayInput([]), $output));
        $text = $output->fetch();
        $this->assertStringContainsString('przerwane', $text);
        $this->assertStringNotContainsString('internal connection details', $text);
    }

    public function test_production_is_rejected_before_any_database_action(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        $command = Mockery::mock(SetupRetroPlayingCards::class)->makePartial();
        $command->__construct();
        $command->shouldNotReceive('call');
        $command->setLaravel($this->app);
        $output = new BufferedOutput;

        $this->assertSame(Command::FAILURE, $command->run(new ArrayInput([]), $output));
        $this->assertStringContainsString('wyłącznie lokalnie', $output->fetch());
    }
}
