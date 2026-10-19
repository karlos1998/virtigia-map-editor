<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class WorldTemplateUpdate extends Model
{
    public const ACTIVE_STATUSES = ['queued', 'comparing', 'queued_apply', 'applying', 'verification_required', 'queued_migration', 'migrating'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'summary' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'snapshot_pruned' => 'boolean',
            'migration_steps' => 'array',
        ];
    }

    public function targetTemplate(): BelongsTo
    {
        return $this->belongsTo(WorldTemplate::class, 'target_template_id');
    }

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(WorldTemplate::class, 'source_template_id');
    }

    public function snapshotRows(): Builder
    {
        return DB::table('world_template_update_rows')->where('world_template_update_id', $this->id);
    }

    public function publicStatus(): array
    {
        return [
            ...$this->only(['id', 'target_template_id', 'source_template_id', 'status', 'progress', 'message', 'summary', 'expires_at', 'completed_at', 'snapshot_pruned', 'created_at', 'migration_steps']),
            'schema_differences' => $this->manifest['schema_differences'] ?? [],
            'table_labels' => config('world_templates.update_table_labels'),
        ];
    }
}
