<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum Profession: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Wojownik')]
    case w = 'w';

    #[Description('Paladyn')]
    case p = 'p';

    #[Description('Mag')]
    case m = 'm';

    #[Description('Tancerz Ostrzy')]
    case b = 'b';

    #[Description('Tropiciel')]
    case t = 't';

    #[Description('Łowca')]
    case h = 'h';
}
