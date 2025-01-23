<?php

namespace App\Enums;

use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseItemRarity: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    case COMMON = 'common';
    case UNIQUE = 'unique';
    case HEROIC = 'heroic';
    case LEGENDARY = 'legendary';
    case UPGRADED = 'upgraded';
    case ARTEFACT = 'artefact';
}
