<?php

namespace App\DTO;

class DialogOptionDto
{
    public ?int $id;
    public ?int $dialogId;
    public string $content;
    public array $rules;
    public array $targetDialogs;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->dialogId = $data['dialogId'] ?? null;
        $this->content = $data['content'];
        $this->rules = $data['rules'] ?? [];
        $this->targetDialogs = $data['targetDialogs'] ?? [];
    }
}
