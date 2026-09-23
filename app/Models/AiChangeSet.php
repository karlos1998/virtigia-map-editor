<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiChangeSet extends Model
{
    /** @use HasFactory<\Database\Factories\AiChangeSetFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_REVERTED = 'reverted';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'world',
        'title',
        'prompt',
        'status',
        'revision',
        'operations',
        'validation',
        'result',
        'before_snapshot',
        'after_snapshot',
        'applied_at',
        'reverted_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiChangeSet $changeSet): void {
            $changeSet->id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'operations' => 'array',
            'validation' => 'array',
            'result' => 'array',
            'before_snapshot' => 'array',
            'after_snapshot' => 'array',
            'applied_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
