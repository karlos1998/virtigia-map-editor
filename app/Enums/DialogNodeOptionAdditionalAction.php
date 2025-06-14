<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

/**
 * Akcje bezposrednio przypisane do opcji dialogowej uruchamiane po jej kliknieciu
 */
enum DialogNodeOptionAdditionalAction: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Ulecz')]
    case HEAL = 'HEAL';

    #[Description('Samobójstwo')]
    case SELF_KILL = 'SELF_KILL';


//    #[Description('Dodaj poziomy')]
//    case ADD_LEVELS = 'ADD_LEVELS';

    #[Description('Cofa doświadczenie')]
    case SUBTRACT_EXP = 'SUBTRACT_EXP';

    #[Description('Rozpoczyna walkę')]
    case BATTLE = 'BATTLE';

    #[Description('Zabij i pokaż okno łupów')]
    case KILL_AND_LOOT = 'KILL_AND_LOOT';

    #[Description('Zabij automatycznie npc')]
    case KILL = 'KILL';

    #[Description('Pokaż pocztę')]
    case SHOW_MAIL = 'SHOW_MAIL';

    #[Description('Pokaż depozyt')]
    case SHOW_DEPOSIT = 'SHOW_DEPOSIT';

    #[Description('Pokaż aukcje')]
    case SHOW_AUCTIONS = 'SHOW_AUCTIONS';
}
