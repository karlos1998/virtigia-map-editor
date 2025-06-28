<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsersController extends Controller
{
    /**
     * @var UserActivityService
     */
    protected $userActivityService;

    /**
     * Create a new controller instance.
     *
     * @param UserActivityService $userActivityService
     */
    public function __construct(UserActivityService $userActivityService)
    {
        $this->userActivityService = $userActivityService;
    }

    /**
     * Display a listing of all users with basic activity information.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => $this->userActivityService->getUsersWithActivityStats(),
        ]);
    }

    /**
     * Display detailed information about a specific user.
     *
     * @param  \App\Models\User  $user
     * @return \Inertia\Response
     */
    public function show(User $user)
    {
        $activityStats = $this->userActivityService->getUserActivityStats($user);

        return Inertia::render('Users/Show', array_merge(
            ['user' => new UserResource($user)],
            $activityStats
        ));
    }
}
