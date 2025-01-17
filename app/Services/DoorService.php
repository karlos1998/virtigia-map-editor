<?php

namespace App\Services;

use App\Models\Door;

final class DoorService
{
    public function __construct(private readonly Door $doorModel)
    {
    }

    public function store(array $validated)
    {
        $this->doorModel->create($validated);
    }
}
