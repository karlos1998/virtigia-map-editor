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
    use GetAttributes;
    use ToDropdownList;
    use ValuesToList;

    #[Description('Dodaj przedmioty')]
    case ADD_ITEMS = 'addItems';

    #[Description('Dodaj złoto')]
    case ADD_GOLD = 'addGold';

    #[Description('Dodaj doświadczenie')]
    case ADD_EXP = 'addExp';

    #[Description('Dodaj doświadczenie (%)')]
    case ADD_EXP_PERCENT = 'addExpPercent';

    #[Description('Ustaw postęp questa')]
    case SET_QUEST_STEP = 'setQuestStep';

    #[Description('Rzuć błogosławieństwo')]
    case BLESSING = 'blessing';

    #[Description('Ustaw outfit')]
    case SET_OUTFIT = 'setOutfit';

    #[Description('Dodaj licznik')]
    case ADD_DIALOG_COUNTER = 'addDialogCounter';

    #[Description('Wyzeruj licznik')]
    case RESET_DIALOG_COUNTER = 'resetDialogCounter';

    #[Description('Resetuj rozdane punkty cech')]
    case RESET_ADDITIONAL_ATTRIBUTE_POINTS = 'resetAdditionalAttributePoints';
}
