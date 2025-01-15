<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseNpcCategory: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('NPC')]
    case NPC = 'NPC';

    #[Description('MOB')]
    case MOB = 'MOB';


}
