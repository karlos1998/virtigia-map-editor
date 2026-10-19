<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'is_production',
        'base_template_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'is_production' => 'boolean',
        ];
    }

    public function baseTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'base_template_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(WorldTemplateUpdate::class, 'target_template_id');
    }

    public function latestUpdate(): HasOne
    {
        return $this->hasOne(WorldTemplateUpdate::class, 'target_template_id')->latestOfMany();
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
