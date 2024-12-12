<?php

namespace App\Http\Controllers;

use App\Services\MapService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{

    public function __construct(private MapService $mapService)
    {
    }

    public function __invoke()
    {
        return Inertia::render('Dashboard', [
            'maps' => $this->mapService->getAll(),
        ]);
    }
}
