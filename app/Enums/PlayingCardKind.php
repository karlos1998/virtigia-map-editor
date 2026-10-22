<?php

namespace App\Enums;

enum PlayingCardKind: string
{
    case UNIT = 'unit';
    case SPECIAL = 'special';
    case WEATHER = 'weather';
    case LEADER = 'leader';
}
