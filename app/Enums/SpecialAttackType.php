<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum SpecialAttackType: string
{

    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Specjalny')]
    case SPECIAL = 'special';

    #[Description('Normalny')]
    case NORMAL = 'normal';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
