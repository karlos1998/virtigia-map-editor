<?php

namespace App\Http\Requests;

use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

abstract class CurrentWorldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function existsOnCurrentWorld(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($this->worldTable($table), $column);
    }

    protected function uniqueOnCurrentWorld(string $table, string $column = 'NULL'): Unique
    {
        return Rule::unique($this->worldTable($table), $column);
    }

    protected function currentWorldConnection(): string
    {
        $world = (string) $this->session()->get('world', get_current_world_template());

        return app(WorldTemplateConnectionResolver::class)->connectionNameFor($world) ?? $world;
    }

    private function worldTable(string $table): string
    {
        return $this->currentWorldConnection().'.'.$table;
    }
}
