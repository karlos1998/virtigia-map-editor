<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Services\DashboardActivityService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardActivityService $dashboardActivityService) {}

    public function __invoke(DashboardRequest $request): Response
    {
        return Inertia::render(
            'Dashboard',
            $this->dashboardActivityService->getAnalytics(
                $request->days(),
                $request->session()->get('world')
            )
        );
    }

    public function locked(): Response
    {
        return Inertia::render('Locked', []);
    }
}
