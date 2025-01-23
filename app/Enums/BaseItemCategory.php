<?php

namespace App\Enums;

use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum BaseItemCategory: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    case ONE_HANDED = 'oneHanded';
    case ARMORS = 'armors';
    case TWO_HANDED = 'twoHanded';
    case HALF_HANDED = 'halfHanded';
    case GLOVES = 'gloves';
    case HELMETS = 'helmets';
    case BOOTS = 'boots';
    case RINGS = 'rings';
    case NECKLACES = 'necklaces';
    case SHIELDS = 'shields';
    case STAFFS = 'staffs';
    case AUXILIARY = 'auxiliary';
    case QUESTS = 'quests';
    case CONSUMABLE = 'consumable';
    case NEUTRALS = 'neutrals';
    case BACKPACKS = 'backpacks';
    case WANDS = 'wands';
    case DISTANCES = 'distances';
    case ARROWS = 'arrows';
    case TALISMANS = 'talismans';
    case UPGRADES = 'upgrades';
    case BOOKS = 'books';
    case KEYS = 'keys';
    case GOLDS = 'golds';
}
