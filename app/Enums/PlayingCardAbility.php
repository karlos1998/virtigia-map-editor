<?php

namespace App\Enums;

enum PlayingCardAbility: string
{
    case SPY = 'spy';
    case MEDIC = 'medic';
    case BOND = 'bond';
    case MORALE = 'morale';
    case HORN = 'horn';
    case MUSTER = 'muster';
    case SCORCH = 'scorch';
    case SCORCH_CLOSE = 'scorch_c';
    case AVENGER = 'avenger';
    case FROST = 'frost';
    case FOG = 'fog';
    case RAIN = 'rain';
    case STORM = 'storm';
    case CLEAR = 'clear';
    case DECOY = 'decoy';
    case LEADER_FOG = 'foltest_king';
    case LEADER_CLEAR = 'foltest_lord';
    case LEADER_HORN = 'foltest_siegemaster';
    case LEADER_CLOSE_SCORCH = 'foltest_son';
    case LEADER_SIEGE_SCORCH = 'foltest_steelforged';
}
