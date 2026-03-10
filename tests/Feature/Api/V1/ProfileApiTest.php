<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\UserApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_bearer_token_to_access_profile(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertUnauthorized();
    }

    public function test_it_returns_authenticated_profile_for_valid_token(): void
    {
        $user = User::query()->create([
            'login' => 'tester',
            'name' => 'Test User',
            'email' => 'tester@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['maps.read'],
        ]);

        $plainToken = 'test_api_token_123';

        UserApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Token',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->getJson('/api/v1/profile');

        $response
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('login', 'tester')
            ->assertJsonPath('email', 'tester@example.com');
    }

    public function test_it_rejects_expired_token(): void
    {
        $user = User::query()->create([
            'login' => 'expired-user',
            'name' => 'Expired User',
            'email' => 'expired@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['maps.read'],
        ]);

        $plainToken = 'expired_api_token_123';

        UserApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'Expired Token',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->getJson('/api/v1/profile');

        $response->assertUnauthorized();
    }
}
