<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseNpcRank: string {

    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;


    #[Description('Zwykły')]
    case NORMAL = 'NORMAL';

    #[Description('Elita')]
    case ELITE = 'ELITE';

    #[Description('Elita II')]
    case ELITE_II = 'ELITE_II';

    #[Description('Elita III')]
    case ELITE_III = 'ELITE_III';

    #[Description('Heros')]
    case HERO = 'HERO';

    #[Description('Tytan')]
    case TITAN = 'TITAN';

}
