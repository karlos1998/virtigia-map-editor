<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\WorldTemplateDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class WorldTemplateAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('world_templates.remote_database_servers.main1.driver', 'sqlite');
    }

    public function test_administrator_can_view_world_templates_page(): void
    {
        $response = $this
            ->actingAs($this->makeAdministrator())
            ->withSession(['world' => 'retro'])
            ->get(route('administration.world-templates.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Administration/WorldTemplates')
                ->has('templates', 5)
                ->where('templates.0.name', 'Classic')
                ->where('templates.0.database_status', 'error')
                ->where('remoteDatabaseServers.0.value', 'main1')
            );
    }

    public function test_administrator_sees_database_connection_problem_for_missing_template_database(): void
    {
        File::delete(database_path('template_missing_database.sqlite'));

        WorldTemplate::query()->create([
            'name' => 'Missing Database',
            'slug' => 'missing_database',
            'connection_name' => 'missing_database',
            'remote_database_server' => 'main1',
            'database_name' => 'template_missing_database.sqlite',
            'is_active' => true,
            'is_visible' => true,
        ]);

        $response = $this
            ->actingAs($this->makeAdministrator())
            ->withSession(['world' => 'retro'])
            ->get(route('administration.world-templates.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('templates.3.name', 'Missing Database')
                ->where('templates.3.database_status', 'error')
                ->where('templates.3.database_status_message', fn (?string $message): bool => $message !== null && str_contains($message, 'does not exist'))
            );
    }

    public function test_administrator_can_request_new_world_template_creation(): void
    {
        $createdTemplate = new WorldTemplate([
            'name' => 'Nowy świat',
            'slug' => 'nowy_swiat',
            'connection_name' => 'nowy_swiat',
            'remote_database_server' => 'main1',
            'database_name' => 'template_nowy_swiat',
            'is_active' => true,
            'is_visible' => true,
        ]);

        $this->mock(WorldTemplateDatabaseService::class, function ($mock) use ($createdTemplate): void {
            $mock->shouldReceive('createTemplate')
                ->once()
                ->with(Mockery::on(fn (array $attributes): bool => $attributes === [
                    'name' => 'Nowy świat',
                    'remote_database_server' => 'main1',
                ]))
                ->andReturn($createdTemplate);
        });

        $response = $this
            ->actingAs($this->makeAdministrator())
            ->withSession(['world' => 'retro'])
            ->post(route('administration.world-templates.store'), [
                'name' => 'Nowy świat',
                'remote_database_server' => 'main1',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Template [Nowy świat] został utworzony.');
    }

    public function test_non_administrator_cannot_create_world_template(): void
    {
        $response = $this
            ->actingAs($this->makeEditor())
            ->withSession(['world' => 'retro'])
            ->post(route('administration.world-templates.store'), [
                'name' => 'Nowy świat',
                'remote_database_server' => 'main1',
            ]);

        $response->assertForbidden();
    }

    private function makeAdministrator(): User
    {
        return $this->makeUser([
            'roles' => [
                ['name' => 'administrator'],
            ],
        ]);
    }

    private function makeEditor(): User
    {
        return $this->makeUser([
            'roles' => [
                ['name' => 'game_master'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeUser(array $attributes): User
    {
        $user = new User([
            'login' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => $attributes['roles'],
            'permissions' => ['world.write'],
        ]);
        $user->id = fake()->unique()->numberBetween(1, 100000);
        $user->exists = true;

        return $user;
    }
}
