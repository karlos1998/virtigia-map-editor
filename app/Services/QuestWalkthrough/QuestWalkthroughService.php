<?php

namespace App\Services\QuestWalkthrough;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps the `quest_walkthroughs` table of a world template in sync with its quest content.
 */
class QuestWalkthroughService
{
    public const TABLE = 'quest_walkthroughs';

    public function __construct(private readonly QuestWalkthroughDataLoader $loader) {}

    /**
     * Regenerates walkthroughs when any source table changed since the last run (or when forced).
     * Only rows whose content actually changed are rewritten, so `generated_at` marks real changes.
     *
     * @return array{skipped: bool, reason?: string, quests?: int, changed?: int, removed?: int}
     */
    public function refreshWorld(string $world, bool $force = false): array
    {
        if (! $this->isAvailable($world)) {
            return ['skipped' => true, 'reason' => 'missing_table'];
        }

        $signature = $this->loader->sourceSignature($world).':v'.QuestWalkthroughGenerator::VERSION;

        if (! $force && Cache::get($this->signatureKey($world)) === $signature) {
            $this->rememberStatus($world, ['last_checked_at' => now()->toIso8601String(), 'last_result' => 'unchanged']);

            return ['skipped' => true, 'reason' => 'unchanged'];
        }

        $data = $this->loader->load($world);
        $generator = new QuestWalkthroughGenerator($data);
        $existing = DB::connection($world)->table(self::TABLE)->pluck('content_hash', 'quest_id');
        $now = now();
        $changed = [];

        foreach (array_keys($data->quests) as $questId) {
            $row = $this->row($questId, $generator->generate($questId), $now);

            if (($existing[$questId] ?? null) !== $row['content_hash']) {
                $changed[] = $row;
            }
        }

        foreach (array_chunk($changed, 50) as $chunk) {
            $this->upsert($world, $chunk);
        }

        DB::connection($world)->table(self::TABLE)->update(['checked_at' => $now]);
        $removed = DB::connection($world)->table(self::TABLE)->whereNotIn('quest_id', array_keys($data->quests))->delete();

        Cache::forever($this->signatureKey($world), $signature);
        $this->rememberStatus($world, [
            'last_checked_at' => $now->toIso8601String(),
            'last_generated_at' => $now->toIso8601String(),
            'last_result' => 'generated',
            'changed' => count($changed),
        ]);

        Log::info('Quest walkthroughs refreshed', ['world' => $world, 'quests' => count($data->quests), 'changed' => count($changed)]);

        return ['skipped' => false, 'quests' => count($data->quests), 'changed' => count($changed), 'removed' => $removed];
    }

    /**
     * Regenerates a single quest immediately.
     */
    public function refreshQuest(string $world, int $questId): array
    {
        $walkthrough = (new QuestWalkthroughGenerator($this->loader->load($world)))->generate($questId);

        $this->upsert($world, [$this->row($questId, $walkthrough, now())]);

        return $walkthrough;
    }

    /**
     * Generates a walkthrough without storing it (used before the first scheduled run).
     */
    public function preview(string $world, int $questId): array
    {
        return (new QuestWalkthroughGenerator($this->loader->load($world)))->generate($questId);
    }

    public function isAvailable(string $world): bool
    {
        return Schema::connection($world)->hasTable(self::TABLE);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(string $world): array
    {
        return Cache::get($this->statusKey($world), []);
    }

    private function row(int $questId, array $walkthrough, mixed $now): array
    {
        $json = json_encode($walkthrough, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            'quest_id' => $questId,
            'generator_version' => QuestWalkthroughGenerator::VERSION,
            'status' => $walkthrough['status'],
            'content_hash' => hash('sha256', $json),
            'walkthrough' => $json,
            'warnings_count' => count($walkthrough['warnings']),
            'generated_at' => $now,
            'checked_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function upsert(string $world, array $rows): void
    {
        DB::connection($world)->table(self::TABLE)->upsert(
            $rows,
            ['quest_id'],
            ['generator_version', 'status', 'content_hash', 'walkthrough', 'warnings_count', 'generated_at', 'checked_at', 'updated_at'],
        );
    }

    private function rememberStatus(string $world, array $status): void
    {
        Cache::forever($this->statusKey($world), [...$this->status($world), ...$status]);
    }

    private function signatureKey(string $world): string
    {
        return "quest_walkthroughs:{$world}:source_signature";
    }

    private function statusKey(string $world): string
    {
        return "quest_walkthroughs:{$world}:status";
    }
}
