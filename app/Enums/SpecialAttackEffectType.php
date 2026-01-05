<?php

namespace App\Enums;

use App\Enums\Attributes\GetAttributes;
use App\Enums\Attributes\Description;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum SpecialAttackEffectType: string
{

    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;

    #[Description('Odepchnięcie')]
    case PUSH_BACK = 'pushBack';

    #[Description('Zatrucie')]
    case POISON = 'poison';

    #[Description('Siła krytyczna fizyczna')]
    case PHYSICAL_CRIT_STRENGTH = 'physicalCritStrength';

    #[Description('Leczenie')]
    case HEALING = 'healing';

    #[Description('Ogłuszenie')]
    case STUN = 'stun';

    #[Description('Obniżenie SA')]
    case DEBUFF_SA = 'debuffSA';

    #[Description('Dodanie SA')]
    case ADD_SA = 'addSA';

    #[Description('Głęboka rana')]
    case DEEP_WOUND = 'deepWound';

    #[Description('Leczenie na turę')]
    case HEALING_PER_TURN = 'healingPerTurn';

    #[Description('Dodanie pancerza')]
    case ADD_ARMOR = 'addArmor';

    #[Description('Dodanie odporności procentowej')]
    case ADD_RESISTANCE_PERCENT = 'addResistancePercent';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
