<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserApiTokenRequest;
use App\Http\Requests\UpdateUserApiTokenRequest;
use App\Models\User;
use App\Models\UserApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $tokens = $user->apiTokens()
            ->latest()
            ->get()
            ->map(static function (UserApiToken $token): array {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'expires_at' => $token->expires_at?->toISOString(),
                    'revoked_at' => $token->revoked_at?->toISOString(),
                    'created_at' => $token->created_at?->toISOString(),
                    'is_active' => $token->revoked_at === null
                        && ($token->expires_at === null || $token->expires_at->isFuture()),
                ];
            })
            ->values();

        return Inertia::render('ApiTokens/Index', [
            'tokens' => $tokens,
        ]);
    }

    public function store(StoreUserApiTokenRequest $request): RedirectResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $plainToken = sprintf('vme_%s', Str::random(64));

        $apiToken = $user->apiTokens()->create([
            'name' => $request->validated('name'),
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $request->validated('expires_at'),
        ]);

        return to_route('api-tokens.index')->with('newApiToken', [
            'id' => $apiToken->id,
            'name' => $apiToken->name,
            'plain_text_token' => $plainToken,
            'expires_at' => $apiToken->expires_at?->toISOString(),
        ]);
    }

    public function update(UpdateUserApiTokenRequest $request, UserApiToken $apiToken): RedirectResponse
    {
        $this->ensureTokenOwner($request, $apiToken);

        $apiToken->update([
            'expires_at' => $request->validated('expires_at'),
        ]);

        return to_route('api-tokens.index');
    }

    public function destroy(Request $request, UserApiToken $apiToken): RedirectResponse
    {
        $this->ensureTokenOwner($request, $apiToken);

        $apiToken->update([
            'revoked_at' => now(),
        ]);

        return to_route('api-tokens.index');
    }

    private function ensureTokenOwner(Request $request, UserApiToken $apiToken): void
    {
        abort_if($request->user()->id !== $apiToken->user_id, 403);
    }
}
