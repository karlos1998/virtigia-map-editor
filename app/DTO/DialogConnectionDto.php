<?php

namespace App\DTO;

class DialogConnectionDto
{
    public ?int $sourceOptionId;
    public ?int $sourceGroupId;
    public int $targetDialogId;
    public array $rules;

    public function __construct($data)
    {
        $this->sourceOptionId = $data['sourceOptionId'] ?? null;
        $this->sourceGroupId = $data['sourceGroupId'] ?? null;
        $this->targetDialogId = $data['targetDialogId'];
        $this->rules = $data['rules'] ?? [];
    }
}
