<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Http\Resources\UserWithActivityResource;
use App\Models\User;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;
use Spatie\Activitylog\Models\Activity;

class UserActivityService extends BaseService
{
    public function __construct(private readonly User $userModel)
    {
    }

    /**
     * Get basic activity statistics for all users.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getUsersWithActivityStats()
    {
        return $this->fetchData(
            UserWithActivityResource::class,
            $this->userModel,
            new TableService(
                columns: [
                    'id' => new TableTextColumn(),
                    'name' => new TableTextColumn(),
                    'email' => new TableTextColumn(),
                    'total_activities' => new TableTextColumn(),
                    'last_activity_at' => new TableTextColumn(),
                ],
                globalFilterColumns: ['name', 'email']
            )
        );
    }

    /**
     * Get detailed activity statistics for a specific user.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    public function getUserActivityStats(User $user)
    {
        // Get user's activity logs
        $userActivities = Activity::where('causer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        // Group activities by subject type for statistics
        $activityByModelType = $userActivities
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
        $activityByEventType = $userActivities
            ->groupBy('event')
            ->map(function ($activities, $event) {
                return [
                    'event' => $event,
                    'count' => $activities->count(),
                ];
            })
            ->values();

        // Group activities by date for timeline
        $activityByDate = $userActivities
            ->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            })
            ->map(function ($activities, $date) {
                return [
                    'date' => $date,
                    'count' => $activities->count(),
                    'activities' => $activities,
                ];
            })
            ->values();

        // Calculate activity by hour of day
        $activityByHour = $userActivities
            ->groupBy(function ($activity) {
                return $activity->created_at->format('H');
            })
            ->map(function ($activities, $hour) {
                return [
                    'hour' => (int)$hour,
                    'count' => $activities->count(),
                ];
            })
            ->sortBy('hour')
            ->values();

        // Calculate work time statistics
        $workTimeStats = $this->calculateWorkTimeStats($userActivities);

        return [
            'recentActivities' => $userActivities->take(20),
            'activityByModelType' => $activityByModelType,
            'activityByEventType' => $activityByEventType,
            'activityByDate' => $activityByDate,
            'activityByHour' => $activityByHour,
            'workTimeStats' => $workTimeStats,
            'totalActivities' => $userActivities->count(),
        ];
    }

    /**
     * Calculate work time statistics based on activity logs.
     *
     * @param  \Illuminate\Support\Collection  $activities
     * @return array
     */
    private function calculateWorkTimeStats($activities)
    {
        if ($activities->isEmpty()) {
            return [
                'total_days' => 0,
                'total_hours' => 0,
                'avg_hours_per_day' => 0,
                'most_active_day' => null,
                'most_active_hour' => null,
            ];
        }

        // Group activities by day
        $activitiesByDay = $activities->groupBy(function ($activity) {
            return $activity->created_at->format('Y-m-d');
        });

        // Calculate total days worked
        $totalDays = $activitiesByDay->count();

        // Calculate approximate hours worked per day
        $hoursPerDay = $activitiesByDay->map(function ($dayActivities) {
            // Sort activities by time
            $sortedActivities = $dayActivities->sortBy('created_at');

            // Get first and last activity of the day
            $firstActivity = $sortedActivities->first();
            $lastActivity = $sortedActivities->last();

            // Calculate time difference in hours (with a maximum of 12 hours per day)
            $hours = min(12, $firstActivity->created_at->diffInHours($lastActivity->created_at) + 1);

            return [
                'date' => $firstActivity->created_at->format('Y-m-d'),
                'hours' => $hours,
                'count' => $dayActivities->count(),
            ];
        })->values();

        // Calculate total hours worked
        $totalHours = $hoursPerDay->sum('hours');

        // Calculate average hours per day
        $avgHoursPerDay = $totalDays > 0 ? round($totalHours / $totalDays, 1) : 0;

        // Find most active day
        $mostActiveDay = $hoursPerDay->sortByDesc('count')->first();

        // Find most active hour
        $mostActiveHour = $activities
            ->groupBy(function ($activity) {
                return $activity->created_at->format('H');
            })
            ->map(function ($hourActivities, $hour) {
                return [
                    'hour' => (int)$hour,
                    'count' => $hourActivities->count(),
                ];
            })
            ->sortByDesc('count')
            ->first();

        return [
            'total_days' => $totalDays,
            'total_hours' => $totalHours,
            'avg_hours_per_day' => $avgHoursPerDay,
            'most_active_day' => $mostActiveDay,
            'most_active_hour' => $mostActiveHour,
            'daily_stats' => $hoursPerDay,
        ];
    }
}
