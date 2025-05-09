<?php

namespace App\Enums;

use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseItemCurrency: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    case GOLD = 'gold';
    case UNSET = 'unset';
    case DRAGON_TEAR = 'dragonTear';
}
