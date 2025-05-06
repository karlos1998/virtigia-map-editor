<?php

namespace App\Http\Controllers;

use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\User;
use App\Services\MapService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Get most active users from activity logs
        $mostActiveUsers = Activity::select('causer_id', DB::raw('count(*) as total_activities'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->orderBy('total_activities', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                $user = User::find($activity->causer_id);
                return [
                    'id' => $activity->causer_id,
                    'name' => $user ? $user->name : 'Unknown User',
                    'total_activities' => $activity->total_activities,
                ];
            });

        // Get recent activity logs
        $recentActivities = Activity::with('causer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => $activity->subject_id,
                    'causer_name' => $activity->causer ? $activity->causer->name : 'System',
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                    'properties' => $activity->properties,
                ];
            });

        // Get activity by model type
        $activityByModelType = Activity::select('subject_type', DB::raw('count(*) as count'))
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                $modelName = class_basename($activity->subject_type);
                return [
                    'model' => $modelName,
                    'count' => $activity->count,
                ];
            });

        // Get base item statistics
        $baseItemCount = BaseItem::count();
        $baseItemsByCategory = BaseItem::select('category', DB::raw('count(*) as count'))
            ->whereNotNull('category')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category ? $item->category->value : 'Uncategorized',
                    'count' => $item->count,
                ];
            });

        // Get base NPC statistics
        $baseNpcCount = BaseNpc::count();
        $baseNpcsByProfession = BaseNpc::select('profession', DB::raw('count(*) as count'))
            ->whereNotNull('profession')
            ->groupBy('profession')
            ->get()
            ->map(function ($npc) {
                return [
                    'profession' => $npc->profession ? $npc->profession->value : 'No Profession',
                    'count' => $npc->count,
                ];
            });

        return Inertia::render('Dashboard', [
            'mostActiveUsers' => $mostActiveUsers,
            'recentActivities' => $recentActivities,
            'activityByModelType' => $activityByModelType,
            'baseItemCount' => $baseItemCount,
            'baseItemsByCategory' => $baseItemsByCategory,
            'baseNpcCount' => $baseNpcCount,
            'baseNpcsByProfession' => $baseNpcsByProfession,
        ]);
    }

    public function locked()
    {
        return Inertia::render('Locked', []);
    }
}
