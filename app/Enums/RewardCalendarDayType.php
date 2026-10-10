<?php

namespace App\Enums;

use App\Enums\Traits\ValuesToList;

enum RewardCalendarDayType: string
{
    use ValuesToList;

    /** Zwykły dzień - nagroda konfigurowana jak zawartość kuferka. */
    case NORMAL = 'normal';

    /** Dzień specjalny - nagroda zależy od liczby wcześniej otwartych dni w kalendarzu. */
    case SPECIAL = 'special';
}
