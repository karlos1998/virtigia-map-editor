<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseItemCurrency: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Złoto')]
    case GOLD = 'gold';

    #[Description('Nie przydzielono')]
    case UNSET = 'unset';

    #[Description('Smocza łza')]
    case DRAGON_TEAR = 'dragonTear';
}
