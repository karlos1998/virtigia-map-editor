<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

/**
 * Akcje przypisane do danej kwesti dialogowej npc. czyli widzimy kwestie i w tym momencie dostajemy bonusy
 */
enum DialogNodeAdditionalAction: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Dodaj przedmioty')]
    case ADD_ITEMS = 'addItems';

    #[Description('Dodaj złoto')]
    case ADD_GOLD = 'addGold';
}
