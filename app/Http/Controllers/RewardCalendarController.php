<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRewardCalendarDayRequest;
use App\Http\Requests\StoreRewardCalendarRequest;
use App\Http\Requests\UpdateRewardCalendarDayRequest;
use App\Http\Requests\UpdateRewardCalendarRequest;
use App\Models\RewardCalendar;
use App\Models\RewardCalendarDay;
use App\Services\RewardCalendarService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RewardCalendarController extends Controller
{
    public function __construct(private readonly RewardCalendarService $rewardCalendarService) {}

    public function index(): Response
    {
        return Inertia::render('RewardCalendar/Index', [
            'calendars' => $this->rewardCalendarService->getAllForList(),
            'styles' => $this->rewardCalendarService->styleOptions(),
        ]);
    }

    public function show(RewardCalendar $rewardCalendar): Response
    {
        return Inertia::render('RewardCalendar/Show', $this->rewardCalendarService->getShowData($rewardCalendar));
    }

    public function store(StoreRewardCalendarRequest $request): RedirectResponse
    {
        $calendar = $this->rewardCalendarService->createCalendar($request->validated());

        return to_route('reward-calendars.show', $calendar)->with('success', 'Kalendarz został utworzony.');
    }

    public function update(UpdateRewardCalendarRequest $request, RewardCalendar $rewardCalendar): RedirectResponse
    {
        $this->rewardCalendarService->updateCalendar($rewardCalendar, $request->validated());

        return back()->with('success', 'Kalendarz został zapisany.');
    }

    public function destroy(RewardCalendar $rewardCalendar): RedirectResponse
    {
        $this->rewardCalendarService->deleteCalendar($rewardCalendar);

        return to_route('reward-calendars.index')->with('success', 'Kalendarz został usunięty.');
    }

    public function storeDay(StoreRewardCalendarDayRequest $request, RewardCalendar $rewardCalendar): RedirectResponse
    {
        $this->rewardCalendarService->createDay($rewardCalendar, $request->validated());

        return back()->with('success', 'Dzień został dodany.');
    }

    public function updateDay(
        UpdateRewardCalendarDayRequest $request,
        RewardCalendar $rewardCalendar,
        RewardCalendarDay $rewardCalendarDay,
    ): RedirectResponse {
        abort_unless($rewardCalendarDay->reward_calendar_id === $rewardCalendar->id, 404);

        $this->rewardCalendarService->updateDay($rewardCalendarDay, $request->validated());

        return back()->with('success', 'Dzień został zapisany.');
    }

    public function destroyDay(RewardCalendar $rewardCalendar, RewardCalendarDay $rewardCalendarDay): RedirectResponse
    {
        abort_unless($rewardCalendarDay->reward_calendar_id === $rewardCalendar->id, 404);

        $this->rewardCalendarService->deleteDay($rewardCalendarDay);

        return back()->with('success', 'Dzień został usunięty.');
    }
}
