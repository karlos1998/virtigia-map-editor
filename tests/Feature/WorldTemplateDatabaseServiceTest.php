<?php

namespace Tests\Feature;

use App\Models\WorldTemplate;
use App\Services\WorldTemplateDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class WorldTemplateDatabaseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rolls_back_template_and_removes_created_database_when_remote_migrations_fail(): void
    {
        config()->set('world_templates.database_prefix', 'testing_transaction_');
        config()->set('world_templates.remote_database_servers.testing', [
            'label' => 'Testing',
            'driver' => 'sqlite',
        ]);

        $databasePath = database_path('testing_transaction_awaria.sqlite');
        File::delete($databasePath);

        Artisan::shouldReceive('call')
            ->once()
            ->andThrow(new RuntimeException('Remote migrations failed.'));

        $this->expectException(RuntimeException::class);

        try {
            app(WorldTemplateDatabaseService::class)->createTemplate([
                'name' => 'Awaria',
                'remote_database_server' => 'testing',
            ]);
        } finally {
            $this->assertDatabaseMissing('world_templates', [
                'slug' => 'awaria',
            ]);
            $this->assertFalse(File::exists($databasePath));
            $this->assertFalse(WorldTemplate::query()->where('slug', 'awaria')->exists());
        }
    }
}
