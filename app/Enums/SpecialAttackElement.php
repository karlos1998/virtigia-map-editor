<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum SpecialAttackElement: string
{

    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Fizyczne')]
    case PHYSICAL = 'physical';

    #[Description('Błyskawice')]
    case LIGHTNING = 'lightning';

    #[Description('Ogień')]
    case FIRE = 'fire';

    #[Description('Dystansowe')]
    case RANGED = 'ranged';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
