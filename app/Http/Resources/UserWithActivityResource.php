<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class UserWithActivityResource extends UserResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get the user's last activity
        $lastActivity = Activity::where('causer_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Count total activities
        $totalActivities = Activity::where('causer_id', $this->id)->count();

        // Get the base user data from the parent resource
        $userData = parent::toArray($request);

        // Add activity statistics
        return array_merge($userData, [
            'last_activity_at' => $lastActivity ? $lastActivity->created_at : null,
            'last_activity_type' => $lastActivity ? $lastActivity->event : null,
            'total_activities' => $totalActivities,
        ]);
    }
}
