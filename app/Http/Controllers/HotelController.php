<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\StoreHotelRoomRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Requests\UpdateHotelRoomRequest;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Services\HotelService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    public function __construct(private readonly HotelService $hotelService) {}

    public function index(): Response
    {
        return Inertia::render('Hotel/Index', [
            'hotels' => HotelResource::collection($this->hotelService->getAll()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Hotel/Create');
    }

    public function store(StoreHotelRequest $request): RedirectResponse
    {
        $hotel = $this->hotelService->create($request->validated());

        return to_route('hotels.show', $hotel->id);
    }

    public function show(Hotel $hotel): Response
    {
        return Inertia::render('Hotel/Show', [
            'hotel' => HotelResource::make($this->hotelService->getById($hotel)),
        ]);
    }

    public function update(Hotel $hotel, UpdateHotelRequest $request): void
    {
        $this->hotelService->update($hotel, $request->validated());
    }

    public function destroy(Hotel $hotel): RedirectResponse
    {
        $this->hotelService->delete($hotel);

        return to_route('hotels.index');
    }

    public function storeRoom(Hotel $hotel, StoreHotelRoomRequest $request): RedirectResponse
    {
        $this->hotelService->createRoom($hotel, $request->validated());

        return to_route('hotels.show', $hotel->id);
    }

    public function updateRoom(Hotel $hotel, HotelRoom $hotelRoom, UpdateHotelRoomRequest $request): RedirectResponse
    {
        abort_unless($hotelRoom->hotel_id === $hotel->id, 404);

        $this->hotelService->updateRoom($hotelRoom, $request->validated());

        return to_route('hotels.show', $hotel->id);
    }

    public function destroyRoom(Hotel $hotel, HotelRoom $hotelRoom): RedirectResponse
    {
        abort_unless($hotelRoom->hotel_id === $hotel->id, 404);

        $this->hotelService->deleteRoom($hotelRoom);

        return to_route('hotels.show', $hotel->id);
    }
}
