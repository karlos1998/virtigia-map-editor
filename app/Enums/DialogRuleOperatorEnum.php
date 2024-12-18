<?php

namespace App\Enums;

enum DialogRuleOperatorEnum: string
{
    case Greater = '>';
    case GreaterEqual = '>=';
    case Less = '<';
    case LessEqual = '<=';
    case Equal = '=';
    case NotEqual = '!=';
    case Has = 'has';
    case HasAll = 'has_all';
}
