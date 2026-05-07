<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SeasonalEvent extends DynamicModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function baseNpcs()
    {
        return $this->belongsToMany(BaseNpc::class, 'base_npc_seasonal_events');
    }

    public function isCurrentlyActive(?Carbon $now = null): bool
    {
        if ($this->active === true) {
            return true;
        }

        if ($this->active === false) {
            return false;
        }

        $now ??= now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return $this->starts_at !== null || $this->ends_at !== null;
    }
}

