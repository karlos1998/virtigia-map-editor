<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\DialogNodeOption\CanBeUsed;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;
use Illuminate\Support\Str;
use ReflectionClassConstant;

enum DialogNodeOptionRule: string
{
    use ValuesToList;
    use ToDropdownList;
    use GetAttributes;
    #[CanBeUsed]
    #[Description('Złoto')]
    case GOLD = 'gold';

    #[Description('Poziom')]
    case LEVEL = 'level';

    #[Description('Karmazynowe bractwo')]
    case BROTHERHOOD = 'brotherhood';

    #[CanBeUsed]
    #[Description('Przedmioty')]
    case ITEMS = 'items';

    #[Description('Procentowa szansa')]
    case PERCENTAGE_CHANCE = 'percentageChance';

    public function canBeUsed(): bool
    {
        $ref = new ReflectionClassConstant(self::class, $this->name);
        $classAttributes = $ref->getAttributes(CanBeUsed::class);

        return count($classAttributes) > 0;
    }

    public static function list()
    {
        return self::toDropdownList(function($case) {
            return [
                'canBeUsed' => $case->canBeUsed()
            ];
        });
    }
}
