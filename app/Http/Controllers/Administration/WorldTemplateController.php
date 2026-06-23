<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorldTemplateRequest;
use App\Models\WorldTemplate;
use App\Services\WorldTemplateConnectionResolver;
use App\Services\WorldTemplateDatabaseService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WorldTemplateController extends Controller
{
    public function __construct(
        private readonly WorldTemplateConnectionResolver $connectionResolver,
        private readonly WorldTemplateDatabaseService $databaseService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Administration/WorldTemplates', [
            'templates' => WorldTemplate::query()
                ->orderBy('name')
                ->get()
                ->map(fn (WorldTemplate $template): array => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'connection_name' => $template->connection_name,
                    'remote_database_server' => $template->remote_database_server,
                    'database_name' => $template->database_name,
                    'is_active' => $template->is_active,
                    'is_visible' => $template->is_visible,
                ]),
            'remoteDatabaseServers' => $this->connectionResolver->remoteDatabaseServerOptions(),
            'defaultRemoteDatabaseServer' => config('world_templates.default_remote_database_server'),
        ]);
    }

    public function store(StoreWorldTemplateRequest $request): RedirectResponse
    {
        try {
            $template = $this->databaseService->createTemplate($request->validated());
        } catch (Throwable $throwable) {
            return back()->withErrors([
                'name' => 'Nie udało się utworzyć template: '.$throwable->getMessage(),
            ]);
        }

        return back()->with('success', "Template [{$template->name}] został utworzony.");
    }
}
