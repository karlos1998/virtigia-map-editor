<?php

namespace Tests\Feature;

use App\Models\WorldTemplate;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetroLocalWorldTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_idempotent_and_resolves_the_existing_database(): void
    {
        config()->set('database.connections.retro_local.database', 'engine_local');
        config()->set('database.connections.retro_local.username', 'engine');

        $this->artisan('world-templates:register-retro-local')->assertSuccessful();
        $this->artisan('world-templates:register-retro-local')->assertSuccessful();

        $this->assertSame(1, WorldTemplate::query()->where('slug', 'retro_local')->count());
        $resolver = app(WorldTemplateConnectionResolver::class);
        $this->assertContains(['value' => 'retro_local', 'label' => 'Retro Local'], $resolver->visibleOptions());
        $this->assertSame('retro_local', $resolver->connectionNameFor('retro_local'));
        $this->assertTrue($resolver->registerTemplateConnection($resolver->resolve('retro_local')));
        $this->assertSame('engine_local', config('database.connections.retro_local.database'));
        $this->assertSame('host.docker.internal', config('database.connections.retro_local.host'));
    }

    public function test_missing_configuration_does_not_register_a_broken_world(): void
    {
        config()->set('database.connections.retro_local.database', null);

        $this->artisan('world-templates:register-retro-local')->assertFailed();

        $this->assertDatabaseMissing('world_templates', ['slug' => 'retro_local']);
    }

    public function test_registration_is_refused_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        $this->artisan('world-templates:register-retro-local')->assertFailed();

        $this->assertDatabaseMissing('world_templates', ['slug' => 'retro_local']);
    }

    public function test_conflicting_world_is_preserved(): void
    {
        config()->set('database.connections.retro_local.database', 'engine_local');
        config()->set('database.connections.retro_local.username', 'engine');
        $world = WorldTemplate::query()->create([
            'name' => 'Existing', 'slug' => 'retro_local', 'connection_name' => 'other',
            'remote_database_server' => 'main1', 'database_name' => 'other',
            'is_active' => true, 'is_visible' => true,
        ]);

        $this->artisan('world-templates:register-retro-local')->assertFailed();

        $this->assertSame('other', $world->fresh()->connection_name);
    }
}
