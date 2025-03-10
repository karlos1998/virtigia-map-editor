<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum DialogNodeOptionAdditionalAction: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Ulecz')]
    case HEAL = 'HEAL';

    #[Description('Zabij')]
    case KILL = 'KILL';

    #[Description('Dodaj poziomy')]
    case ADD_LEVELS = 'ADD_LEVELS';

    #[Description('Cofa doświadczenie')]
    case SUBTRACT_EXP = 'SUBTRACT_EXP';
}
