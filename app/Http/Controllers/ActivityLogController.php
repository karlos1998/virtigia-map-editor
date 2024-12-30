<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    /**
     * @throws \Exception
     */
    public function index()
    {
        return Inertia::render('ActivityLog/Index', [
            'logs' => $this->activityLogService->getAll(),
        ]);
    }
}
