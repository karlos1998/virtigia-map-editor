<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ProfileController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    /**
     * Display the user's profile page.
     *
     * @return \Inertia\Response
     */
    public function show()
    {
        $user = Auth::user();

        // Get user's activity logs
        $userActivities = Activity::where('causer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // Group activities by subject type for statistics
        $activityStats = $userActivities
            ->groupBy('subject_type')
            ->map(function ($activities, $type) {
                // Extract the model name from the full namespace
                $parts = explode('\\', $type);
                $modelName = end($parts);

                return [
                    'model' => $modelName,
                    'count' => $activities->count(),
                    'last_activity' => $activities->first()->created_at,
                ];
            })
            ->values();

        // Group activities by event type
        $eventStats = $userActivities
            ->groupBy('event')
            ->map(function ($activities, $event) {
                return [
                    'event' => $event,
                    'count' => $activities->count(),
                ];
            })
            ->values();

        // Get recent activities
        $recentActivities = $userActivities->take(10);

        return Inertia::render('Profile', [
            'userActivities' => $recentActivities,
            'activityStats' => $activityStats,
            'eventStats' => $eventStats,
        ]);
    }
}
