<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapTrackCheckpointTile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'x',
        'y',
    ];

    protected $casts = [
        'x' => 'integer',
        'y' => 'integer',
    ];

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(MapTrackCheckpoint::class, 'map_track_checkpoint_id');
    }
}
