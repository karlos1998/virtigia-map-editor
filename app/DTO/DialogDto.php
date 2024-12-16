<?php

namespace App\DTO;

class DialogDto
{
    public ?int $id;
    public int $groupId;
    public string $title;
    public string $content;
    public array $options;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->groupId = $data['groupId'];
        $this->title = $data['title'];
        $this->content = $data['content'];
        $this->options = $data['options'] ?? [];
    }
}
