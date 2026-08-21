<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapTrack extends DynamicModel
{
    protected $fillable = [
        'name',
        'dialog_counter_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function dialogCounter(): BelongsTo
    {
        return $this->belongsTo(DialogCounter::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(MapTrackCheckpoint::class)->orderBy('sequence');
    }
}
