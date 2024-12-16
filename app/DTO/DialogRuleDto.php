<?php

namespace App\DTO;

use App\Enums\DialogRuleEntityEnum;
use App\Enums\DialogRuleOperatorEnum;

class DialogRuleDto
{
    public DialogRuleEntityEnum $entityType;
    public DialogRuleOperatorEnum $operator;
    public string $value;

    public function __construct($data)
    {
        $this->entityType = DialogRuleEntityEnum::from($data['entityType']);
        $this->operator = DialogRuleOperatorEnum::from($data['operator']);
        $this->value = $data['value'];
    }
}
