<?php

namespace App\Enums;

enum DialogCounterScope: string
{
    case CHARACTER = 'character';

    case USER = 'user';

    case GLOBAL = 'global';
}
