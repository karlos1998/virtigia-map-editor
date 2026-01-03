<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseItemCategory: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Jednoręczne')]
    case ONE_HANDED = 'oneHanded';

    #[Description('Zbroje')]
    case ARMORS = 'armors';

    #[Description('Dwuręczne')]
    case TWO_HANDED = 'twoHanded';

    #[Description('Półtoraręczne')]
    case HALF_HANDED = 'halfHanded';

    #[Description('Rękawice')]
    case GLOVES = 'gloves';

    #[Description('Hełmy')]
    case HELMETS = 'helmets';

    #[Description('Buty')]
    case BOOTS = 'boots';

    #[Description('Pierścienie')]
    case RINGS = 'rings';

    #[Description('Naszyjniki')]
    case NECKLACES = 'necklaces';

    #[Description('Tarcze')]
    case SHIELDS = 'shields';

    #[Description('Kostury')]
    case STAFFS = 'staffs';

    #[Description('Pomocnicze')]
    case AUXILIARY = 'auxiliary';

    #[Description('Przedmioty fabularne')]
    case QUESTS = 'quests';

    #[Description('Konsumpcyjne')]
    case CONSUMABLE = 'consumable';

    #[Description('Neutralne')]
    case NEUTRALS = 'neutrals';

    #[Description('Plecaki')]
    case BACKPACKS = 'backpacks';

    #[Description('Różdżki')]
    case WANDS = 'wands';

    #[Description('Dystansowe')]
    case DISTANCES = 'distances';

    #[Description('Strzały')]
    case ARROWS = 'arrows';

    #[Description('Talizmany')]
    case TALISMANS = 'talismans';

    #[Description('Ulepszenia')]
    case UPGRADES = 'upgrades';

    #[Description('Książki')]
    case BOOKS = 'books';

    #[Description('Klucze')]
    case KEYS = 'keys';

    #[Description('Złoto')]
    case GOLDS = 'golds';

    #[Description('Błogosławieństwo')]
    case BLESSING = 'blessings';

    #[Description('Zwierzaki')]
    case PETS = 'pets';
}
