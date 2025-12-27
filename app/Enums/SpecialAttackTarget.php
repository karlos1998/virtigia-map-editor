<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum SpecialAttackTarget: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Pojedynczy')]
    case SINGLE = 'single';

    #[Description('Wszyscy')]
    case ALL = 'all';

    #[Description('Własny')]
    case SELF = 'self';

    #[Description('Linia')]
    case LINE = 'line';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
