<?php

namespace App\Http\Controllers;

use App\Models\AiChangeSet;
use App\Models\User;
use App\Services\Mcp\AiChangeSetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AiChangeSetController extends Controller
{
    public function __construct(
        private readonly AiChangeSetService $changeSetService,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $world = (string) $request->session()->get('world', config('services.virtigia_mcp.default_world', 'retro'));

        $changeSets = AiChangeSet::query()
            ->with('user:id,name')
            ->where('world', $world)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (AiChangeSet $changeSet): array => [
                ...$this->changeSetService->present($changeSet),
                'user' => $changeSet->user?->only(['id', 'name']),
                'can_revert' => $changeSet->status === AiChangeSet::STATUS_APPLIED
                    && ($changeSet->user_id === $user->id || $user->hasAdministratorRole()),
            ]);

        return Inertia::render('AiChangeSet/Index', [
            'changeSets' => $changeSets,
            'world' => $world,
        ]);
    }

    public function revert(Request $request, AiChangeSet $aiChangeSet): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($aiChangeSet->user_id === $user->id || $user->hasAdministratorRole(), 403);

        try {
            $owner = $aiChangeSet->user()->firstOrFail();
            $this->changeSetService->revert($owner, $aiChangeSet->id);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Commit AI został cofnięty.');
    }
}
