<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum HotelPeriod: string
{
    use GetAttributes;
    use ToDropdownList;
    use ValuesToList;

    #[Description('Tydzień')]
    case WEEK = 'week';

    #[Description('Miesiąc')]
    case MONTH = 'month';

    #[Description('Rok')]
    case YEAR = 'year';
}
