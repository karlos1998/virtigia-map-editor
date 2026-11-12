<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Attributes\GetAttributes;
use App\Enums\Traits\ToDropdownList;
use App\Enums\Traits\ValuesToList;

/**
 * Group a map belongs to (caves, fields, sea...). Shared by features that treat similar maps alike, e.g. background music in the game client.
 */
enum MapGroup: string
{
    use GetAttributes;
    use ToDropdownList;
    use ValuesToList;

    #[Description('Bez grupy')]
    case NO_GROUP = 'no_group';

    #[Description('Mroczne jaskinie')]
    case BAD_CAVES = 'bad_caves';

    #[Description('Jaskinie')]
    case CAVES = 'caves';

    #[Description('Mroczne tereny')]
    case BAD_FIELDS = 'bad_fields';

    #[Description('Tereny otwarte')]
    case FIELDS = 'fields';

    #[Description('Wnętrza')]
    case INSIDES = 'insides';

    #[Description('Mroczne wnętrza')]
    case BAD_INSIDES = 'bad_insides';

    #[Description('Morze')]
    case SEA = 'sea';

    #[Description('Pustynia')]
    case DESERT = 'desert';
}
