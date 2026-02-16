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

    #[Description('Dodaj doświadczenie')]
    case ADD_EXP = 'addExp';

    #[Description('Ustaw postęp questa')]
    case SET_QUEST_STEP = 'setQuestStep';

    #[Description('Rzuć błogosławieństwo')]
    case BLESSING = 'blessing';

    #[Description('Ustaw outfit')]
    case SET_OUTFIT = 'setOutfit';

    #[Description('Dodaj licznik')]
    case ADD_DIALOG_COUNTER = 'addDialogCounter';
}
