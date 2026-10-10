<?php

namespace App\Enums;

use App\Enums\Traits\ValuesToList;

enum RewardCalendarStyle: string
{
    use ValuesToList;

    case BLUE = 'blue';

    case RED = 'red';

    case ORANGE = 'orange';

    public function label(): string
    {
        return match ($this) {
            self::BLUE => 'Niebieski',
            self::RED => 'Czerwony',
            self::ORANGE => 'Pomarańczowy',
        };
    }
}
