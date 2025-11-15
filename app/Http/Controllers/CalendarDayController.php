<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddBaseItemToCalendarDayRequest;
use App\Http\Resources\CalendarDayResource;
use App\Models\CalendarDay;
use App\Models\RewardCalendarItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarDayController extends Controller
{
    public function index()
    {
        $days = CalendarDay::with('items.baseItem')->orderBy('month')->orderBy('day')->get();

        return Inertia::render('CalendarDays/Index', [
            'days' => CalendarDayResource::collection($days),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'day' => 'required|integer|between:1,31',
            'month' => 'required|integer|between:1,12',
            'year' => 'nullable|integer',
            'name' => 'nullable|string',
        ]);

        CalendarDay::create($validated);

    }

    public function addItem(AddBaseItemToCalendarDayRequest $request)
    {
        $validated = $request->validated();

        $item = RewardCalendarItem::create([
            'calendar_day_id' => $validated['calendarDayId'],
            'base_item_id' => $validated['baseItemId'],
            'quantity' => $validated['quantity'] ?? 1,
        ]);
    }

    public function removeItem(Request $request, int $id)
    {
        $item = RewardCalendarItem::findOrFail($id);
        $item->delete();
    }

    public function destroy(CalendarDay $day)
    {
        $day->delete();
    }
}
