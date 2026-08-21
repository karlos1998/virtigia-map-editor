<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapTrackCheckpoint extends DynamicModel
{
    protected $fillable = [
        'map_track_id',
        'map_id',
        'name',
        'sequence',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function track(): BelongsTo
    {
        return $this->belongsTo(MapTrack::class, 'map_track_id');
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    public function tiles(): HasMany
    {
        return $this->hasMany(MapTrackCheckpointTile::class)->orderBy('y')->orderBy('x');
    }
}
