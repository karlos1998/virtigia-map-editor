<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WorldTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'connection_name',
        'remote_database_server',
        'database_name',
        'is_active',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toOption(): array
    {
        return [
            'value' => $this->slug,
            'label' => $this->name,
        ];
    }
}
