<?php

namespace App\Http\Controllers;

use App\Services\MapService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{

    public function __invoke()
    {
        return Inertia::render('Dashboard', [
        ]);
    }

    public function locked()
    {
        return Inertia::render('Locked', []);
    }
}
