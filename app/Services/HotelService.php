<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelRoom;
use Illuminate\Database\Eloquent\Collection;

final readonly class HotelService
{
    public function __construct(private Hotel $hotelModel, private HotelRoom $hotelRoomModel) {}

    public function getAll(): Collection
    {
        return $this->hotelModel
            ->newQuery()
            ->withCount('rooms')
            ->orderBy('name')
            ->get();
    }

    public function getById(Hotel $hotel): Hotel
    {
        return $hotel->load([
            'rooms.baseItem',
            'rooms.door.map',
            'rooms.door.targetMap',
            'rooms.door.requiredBaseItem',
        ]);
    }

    public function create(array $validated): Hotel
    {
        /** @var Hotel $hotel */
        $hotel = $this->hotelModel->newQuery()->create($validated);

        return $hotel;
    }

    public function update(Hotel $hotel, array $validated): void
    {
        $hotel->update($validated);
    }

    public function delete(Hotel $hotel): void
    {
        $hotel->delete();
    }

    public function createRoom(Hotel $hotel, array $validated): HotelRoom
    {
        /** @var HotelRoom $hotelRoom */
        $hotelRoom = $this->hotelRoomModel->newQuery()->create([
            ...$validated,
            'hotel_id' => $hotel->id,
        ]);

        return $hotelRoom;
    }

    public function updateRoom(HotelRoom $hotelRoom, array $validated): void
    {
        $hotelRoom->update($validated);
    }

    public function deleteRoom(HotelRoom $hotelRoom): void
    {
        $hotelRoom->delete();
    }
}
