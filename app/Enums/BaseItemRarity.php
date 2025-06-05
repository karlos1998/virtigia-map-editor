<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseItemRarity: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Zwykły')]
    case COMMON = 'common';

    #[Description('Unikat')]
    case UNIQUE = 'unique';

    #[Description('Heroik')]
    case HEROIC = 'heroic';

    #[Description('Legendarny')]
    case LEGENDARY = 'legendary';

    #[Description('Ulepszony')]
    case UPGRADED = 'upgraded';

    #[Description('Artefakt')]
    case ARTEFACT = 'artefact';
}
