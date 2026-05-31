<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

enum LegendaryBonus: string
{
    use GetAttributes;
    use ToDropdownList;
    use ValuesToList;

    #[Description('Dotyk anioła')]
    case AngelTouchHealingChance = 'angelTouchHealingChance';

    #[Description('Cios bardzo krytyczny')]
    case SuperCriticalHitChance = 'superCriticalHitChance';

    #[Description('Ochrona żywiołów')]
    case SuperMagicalReduction = 'superMagicalReduction';

    #[Description('Krytyczna osłona')]
    case SuperCriticalReduction = 'superCriticalReduction';

    #[Description('Fizyczna osłona')]
    case SuperPhysicalReduction = 'superPhysicalReduction';

    #[Description('Klątwa')]
    case CurseChanceAfterDoHit = 'curseChanceAfterDoHit';

    #[Description('Odrzut')]
    case PushBack = 'pushBack';

    #[Description('Ostatni ratunek')]
    case SuperHealOnLowHealth = 'superHealOnLowHealth';

    public function bonusValue(): int
    {
        return match ($this) {
            self::AngelTouchHealingChance => 5,
            self::SuperCriticalHitChance => 10,
            self::SuperMagicalReduction => 12,
            self::SuperCriticalReduction => 15,
            self::SuperPhysicalReduction => 12,
            self::CurseChanceAfterDoHit => 7,
            self::PushBack => 8,
            self::SuperHealOnLowHealth => 30,
        };
    }
}
