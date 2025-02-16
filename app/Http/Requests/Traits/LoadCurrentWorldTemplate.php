<?php

namespace App\Http\Requests\Traits;

trait LoadCurrentWorldTemplate
{

    protected readonly string $selectedDatabase;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        $this->selectedDatabase = get_current_world_template();
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
    }
}
