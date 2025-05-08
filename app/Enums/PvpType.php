<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum PvpType: int
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Brak')]
    case NONE = 0;       // 0 - brak pvp

    #[Description('Zgoda')]
    case CONSENT = 1;    // 1 - za zgodą

    #[Description('Arena')]
    case ARENA = 2;      // 2 - arena

    #[Description('Dozwolone')]
    case ALLOWED = 3;     // 3 - dozwolone
}
