<?php

namespace App\Console\Commands;

use App\Enums\BaseNpcRank;
use App\Models\BaseNpc;
use App\Models\DynamicModel;
use App\Models\SpecialAttack;
use App\Models\SpecialAttackDamage;
use App\Models\SpecialAttackEffect;
use App\Services\WorldTemplateConnectionResolver;
use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

class RebalanceTitanSpecialAttacksCommand extends Command
{
    private const DEFAULT_FILE = 'resources/titan-special-attacks/maddok-magua.json';

    protected $signature = 'titans:rebalance-special-attacks
        {file? : JSON file with target titan special attack values}
        {--world= : World slug. Defaults to the configured world template}
        {--apply : Persist target values to the selected world database}';

    protected $description = 'Compare titan special attack target values from JSON and optionally persist them';

    public function handle(WorldTemplateConnectionResolver $connectionResolver): int
    {
        $file = (string) ($this->argument('file') ?: self::DEFAULT_FILE);
        $path = $this->resolveJsonPath($file);

        if ($path === null) {
            $this->error("JSON file [{$file}] does not exist.");

            return self::FAILURE;
        }

        $worldConnection = $this->resolveWorldConnection($connectionResolver);

        if ($worldConnection === null) {
            return self::FAILURE;
        }

        [$world, $connection] = $worldConnection;
        DynamicModel::setGlobalConnection($connection);

        try {
            $payload = $this->readJson($path);
            $report = $this->buildReport($payload, $connection);

            $this->renderReport($world, $connection, $path, $report);

            if ($report['errors'] !== []) {
                return self::FAILURE;
            }

            if ($this->option('apply')) {
                $this->applyChanges($report['changes'], $connection);
                $this->info('Zapisano zmiany w bazie świata.');
            } else {
                $this->warn('Dry-run: baza nie została zmieniona. Dodaj --apply, żeby zapisać target z JSON.');
            }
        } catch (Throwable $throwable) {
            $this->error('Could not rebalance titan special attacks: '.$throwable->getMessage());

            return self::FAILURE;
        } finally {
            DynamicModel::clearGlobalConnection();
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function resolveWorldConnection(WorldTemplateConnectionResolver $connectionResolver): ?array
    {
        $world = (string) ($this->option('world') ?: $connectionResolver->defaultWorldSlug());
        $availableWorlds = $connectionResolver->visibleSlugs();

        if ($availableWorlds !== [] && ! in_array($world, $availableWorlds, true)) {
            $this->error('Unsupported world ['.$world.']. Allowed values: '.implode(', ', $availableWorlds).'.');

            return null;
        }

        $template = $connectionResolver->resolve($world);

        if ($template === null) {
            return [$world, $connectionResolver->connectionNameFor($world) ?? $world];
        }

        if (! $connectionResolver->registerTemplateConnection($template)) {
            $this->error("Could not register database connection for world [{$world}].");

            return null;
        }

        return [$world, $template->connection_name];
    }

    private function resolveJsonPath(string $file): ?string
    {
        $candidates = str_starts_with($file, DIRECTORY_SEPARATOR)
            ? [$file]
            : [base_path($file), $file];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function readJson(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Could not read JSON file [{$path}].");
        }

        $payload = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new \RuntimeException('Titan JSON must contain an object.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{npc: array<string, mixed>|null, summaries: array<int, array<string, mixed>>, changes: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    private function buildReport(array $payload, string $connection): array
    {
        $errors = [];
        $changes = [];
        $summaries = [];
        $npcId = $this->integerValue($payload['id'] ?? null);

        if ($npcId === null) {
            return [
                'npc' => null,
                'summaries' => [],
                'changes' => [],
                'errors' => ['JSON must contain numeric titan id.'],
            ];
        }

        $npc = BaseNpc::query()
            ->with([
                'specialAttacks' => function (BelongsToMany $query): void {
                    $query->orderBy('id');
                },
                'specialAttacks.damages' => function (HasMany $query): void {
                    $query->orderBy('id');
                },
                'specialAttacks.effects' => function (HasMany $query): void {
                    $query->orderBy('id');
                },
            ])
            ->find($npcId);

        if ($npc === null) {
            return [
                'npc' => null,
                'summaries' => [],
                'changes' => [],
                'errors' => ["Titan #{$npcId} was not found on selected world database."],
            ];
        }

        if ($this->enumValue($npc->rank) !== BaseNpcRank::TITAN->value) {
            $errors[] = "Base NPC #{$npc->id} is not a titan.";
        }

        $payloadAttacks = $payload['special_attacks'] ?? [];
        if (! is_array($payloadAttacks) || $payloadAttacks === []) {
            $errors[] = 'JSON must contain non-empty special_attacks array.';
        }

        foreach ($payloadAttacks as $payloadAttack) {
            if (! is_array($payloadAttack)) {
                $errors[] = 'Every special_attacks entry must be an object.';

                continue;
            }

            $attackId = $this->integerValue($payloadAttack['id'] ?? null);
            if ($attackId === null) {
                $errors[] = 'Every special attack must contain numeric id.';

                continue;
            }

            /** @var SpecialAttack|null $attack */
            $attack = $npc->specialAttacks->firstWhere('id', $attackId);
            if ($attack === null) {
                $errors[] = "Attack #{$attackId} is not assigned to titan #{$npc->id}.";

                continue;
            }

            $beforeScore = $this->attackScore($attack);
            $afterScore = $this->targetAttackScore($attack, $payloadAttack);
            $attackChangesBefore = count($changes);

            $this->compareAttackFields($attack, $payloadAttack, $changes);
            $this->compareDamages($attack, $payloadAttack, $changes, $errors);
            $this->compareEffects($attack, $payloadAttack, $changes, $errors);

            $summaries[] = [
                'id' => $attack->id,
                'name' => $attack->name,
                'before_score' => $beforeScore,
                'after_score' => $afterScore,
                'weakening_percent' => $this->percentReduction($beforeScore, $afterScore),
                'changes_count' => count($changes) - $attackChangesBefore,
            ];
        }

        return [
            'npc' => [
                'id' => $npc->id,
                'name' => $npc->name,
                'level' => $npc->lvl,
            ],
            'summaries' => $summaries,
            'changes' => $changes,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $payloadAttack
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function compareAttackFields(SpecialAttack $attack, array $payloadAttack, array &$changes): void
    {
        if (array_key_exists('charge_turns', $payloadAttack)) {
            $this->addChangeIfDifferent(
                $changes,
                $attack,
                'special_attacks',
                $attack->id,
                'charge_turns',
                'charge_turns',
                $attack->charge_turns,
                $this->integerValue($payloadAttack['charge_turns'])
            );
        }

        if (array_key_exists('target', $payloadAttack)) {
            $this->addChangeIfDifferent(
                $changes,
                $attack,
                'special_attacks',
                $attack->id,
                'target',
                'target',
                $this->enumValue($attack->target),
                $this->enumValue($payloadAttack['target'])
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payloadAttack
     * @param  array<int, array<string, mixed>>  $changes
     * @param  array<int, string>  $errors
     */
    private function compareDamages(SpecialAttack $attack, array $payloadAttack, array &$changes, array &$errors): void
    {
        $payloadDamages = $payloadAttack['damages'] ?? [];
        if (! is_array($payloadDamages)) {
            $errors[] = "Attack #{$attack->id} damages must be an array.";

            return;
        }

        foreach ($payloadDamages as $payloadDamage) {
            if (! is_array($payloadDamage)) {
                $errors[] = "Attack #{$attack->id} damage entry must be an object.";

                continue;
            }

            $damage = $this->findDamage($attack, $payloadDamage);
            if ($damage === null) {
                $errors[] = "Damage for attack #{$attack->id} was not found: ".$this->identityLabel($payloadDamage, 'element');

                continue;
            }

            foreach (['min_damage', 'max_damage'] as $column) {
                if (! array_key_exists($column, $payloadDamage)) {
                    continue;
                }

                $this->addChangeIfDifferent(
                    $changes,
                    $attack,
                    'special_attack_damages',
                    $damage->id,
                    $column,
                    'damage '.$this->enumValue($damage->element).' '.$column,
                    $damage->{$column},
                    $this->numericValue($payloadDamage[$column])
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payloadAttack
     * @param  array<int, array<string, mixed>>  $changes
     * @param  array<int, string>  $errors
     */
    private function compareEffects(SpecialAttack $attack, array $payloadAttack, array &$changes, array &$errors): void
    {
        $payloadEffects = $payloadAttack['effects'] ?? [];
        if (! is_array($payloadEffects)) {
            $errors[] = "Attack #{$attack->id} effects must be an array.";

            return;
        }

        foreach ($payloadEffects as $payloadEffect) {
            if (! is_array($payloadEffect)) {
                $errors[] = "Attack #{$attack->id} effect entry must be an object.";

                continue;
            }

            $effect = $this->findEffect($attack, $payloadEffect);
            if ($effect === null) {
                $errors[] = "Effect for attack #{$attack->id} was not found: ".$this->identityLabel($payloadEffect, 'type');

                continue;
            }

            foreach (['value', 'duration'] as $column) {
                if (! array_key_exists($column, $payloadEffect)) {
                    continue;
                }

                $this->addChangeIfDifferent(
                    $changes,
                    $attack,
                    'special_attack_effects',
                    $effect->id,
                    $column,
                    'effect '.$this->enumValue($effect->type).' '.$column,
                    $effect->{$column},
                    $this->numericValue($payloadEffect[$column])
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function addChangeIfDifferent(
        array &$changes,
        SpecialAttack $attack,
        string $table,
        int $rowId,
        string $column,
        string $metric,
        mixed $before,
        mixed $after
    ): void {
        if ($after === null || $before == $after) {
            return;
        }

        $changes[] = [
            'attack_id' => $attack->id,
            'attack_name' => $attack->name,
            'table' => $table,
            'row_id' => $rowId,
            'column' => $column,
            'metric' => $metric,
            'before' => $before,
            'after' => $after,
            'weakening_percent' => is_numeric($before) && is_numeric($after)
                ? $this->percentReduction((float) $before, (float) $after)
                : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function applyChanges(array $changes, string $connection): void
    {
        DB::connection($connection)->transaction(function () use ($changes, $connection): void {
            foreach ($changes as $change) {
                DB::connection($connection)
                    ->table($change['table'])
                    ->where('id', $change['row_id'])
                    ->update([
                        $change['column'] => $change['after'],
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    /**
     * @param  array{npc: array<string, mixed>|null, summaries: array<int, array<string, mixed>>, changes: array<int, array<string, mixed>>, errors: array<int, string>}  $report
     */
    private function renderReport(string $world, string $connection, string $path, array $report): void
    {
        $npc = $report['npc'];

        $this->info('World: '.$world.' ['.$connection.']');
        $this->line('JSON: '.$path);
        if ($npc !== null) {
            $this->line("Titan: #{$npc['id']} {$npc['name']} ({$npc['level']} lvl)");
        }

        if ($report['summaries'] !== []) {
            $this->newLine();
            $this->table(
                ['ID', 'Cios', 'Indeks teraz', 'Indeks po', 'Osłabienie', 'Zmian'],
                array_map(fn (array $summary): array => [
                    $summary['id'],
                    $summary['name'],
                    $this->formatNumber($summary['before_score']),
                    $this->formatNumber($summary['after_score']),
                    $this->formatPercent($summary['weakening_percent']),
                    $summary['changes_count'],
                ], $report['summaries'])
            );
        }

        if ($report['changes'] !== []) {
            $this->newLine();
            $this->table(
                ['Cios', 'Pole', 'Teraz', 'Target', 'Zmiana'],
                array_map(fn (array $change): array => [
                    "#{$change['attack_id']} ".$change['attack_name'],
                    $change['metric'],
                    $this->formatMixed($change['before']),
                    $this->formatMixed($change['after']),
                    $this->formatPercent($change['weakening_percent']),
                ], $report['changes'])
            );
        } else {
            $this->newLine();
            $this->info('Brak zmian względem bazy.');
        }

        foreach ($report['errors'] as $error) {
            $this->error($error);
        }
    }

    private function attackScore(SpecialAttack $attack): float
    {
        $damageScore = $attack->damages->sum(
            fn (SpecialAttackDamage $damage): float => (float) $damage->min_damage + (float) $damage->max_damage
        );
        $effectScore = $attack->effects->sum(fn (SpecialAttackEffect $effect): float => $this->effectScore($effect->value, $effect->duration));

        return (float) $damageScore + (float) $effectScore;
    }

    /**
     * @param  array<string, mixed>  $payloadAttack
     */
    private function targetAttackScore(SpecialAttack $attack, array $payloadAttack): float
    {
        $score = 0.0;

        foreach ($attack->damages as $damage) {
            $payloadDamage = $this->findPayloadDamage($payloadAttack, $damage);
            $score += (float) ($payloadDamage['min_damage'] ?? $damage->min_damage);
            $score += (float) ($payloadDamage['max_damage'] ?? $damage->max_damage);
        }

        foreach ($attack->effects as $effect) {
            $payloadEffect = $this->findPayloadEffect($payloadAttack, $effect);
            $value = $payloadEffect['value'] ?? $effect->value;
            $duration = $payloadEffect['duration'] ?? $effect->duration;
            $score += $this->effectScore($value, $duration);
        }

        return $score;
    }

    private function effectScore(mixed $value, mixed $duration): float
    {
        $numericValue = (float) ($value ?? 0);
        $numericDuration = $duration === null ? 1.0 : max(1.0, (float) $duration);

        return $numericValue * $numericDuration;
    }

    /**
     * @param  array<string, mixed>  $payloadDamage
     */
    private function findDamage(SpecialAttack $attack, array $payloadDamage): ?SpecialAttackDamage
    {
        $damageId = $this->integerValue($payloadDamage['id'] ?? null);
        if ($damageId !== null) {
            return $attack->damages->firstWhere('id', $damageId);
        }

        $element = $this->enumValue($payloadDamage['element'] ?? null);

        return $attack->damages->first(
            fn (SpecialAttackDamage $damage): bool => $this->enumValue($damage->element) === $element
        );
    }

    /**
     * @param  array<string, mixed>  $payloadEffect
     */
    private function findEffect(SpecialAttack $attack, array $payloadEffect): ?SpecialAttackEffect
    {
        $effectId = $this->integerValue($payloadEffect['id'] ?? null);
        if ($effectId !== null) {
            return $attack->effects->firstWhere('id', $effectId);
        }

        $type = $this->enumValue($payloadEffect['type'] ?? null);

        return $attack->effects->first(
            fn (SpecialAttackEffect $effect): bool => $this->enumValue($effect->type) === $type
        );
    }

    /**
     * @param  array<string, mixed>  $payloadAttack
     * @return array<string, mixed>
     */
    private function findPayloadDamage(array $payloadAttack, SpecialAttackDamage $damage): array
    {
        foreach (($payloadAttack['damages'] ?? []) as $payloadDamage) {
            if (! is_array($payloadDamage)) {
                continue;
            }

            $damageId = $this->integerValue($payloadDamage['id'] ?? null);
            if ($damageId !== null && $damageId === $damage->id) {
                return $payloadDamage;
            }

            if ($this->enumValue($payloadDamage['element'] ?? null) === $this->enumValue($damage->element)) {
                return $payloadDamage;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payloadAttack
     * @return array<string, mixed>
     */
    private function findPayloadEffect(array $payloadAttack, SpecialAttackEffect $effect): array
    {
        foreach (($payloadAttack['effects'] ?? []) as $payloadEffect) {
            if (! is_array($payloadEffect)) {
                continue;
            }

            $effectId = $this->integerValue($payloadEffect['id'] ?? null);
            if ($effectId !== null && $effectId === $effect->id) {
                return $payloadEffect;
            }

            if ($this->enumValue($payloadEffect['type'] ?? null) === $this->enumValue($effect->type)) {
                return $payloadEffect;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function identityLabel(array $payload, string $key): string
    {
        $id = $payload['id'] ?? null;
        $value = $payload[$key] ?? null;

        return 'id='.($id ?? '-').', '.$key.'='.($this->enumValue($value) ?? '-');
    }

    private function percentReduction(float $before, float $after): ?float
    {
        if ($before == 0.0) {
            return $after == 0.0 ? 0.0 : null;
        }

        return (($before - $after) / $before) * 100;
    }

    private function integerValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }

        return null;
    }

    private function numericValue(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return null;
    }

    private function enumValue(mixed $value): string|int|null
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            return $value['value'] ?? null;
        }

        if (is_string($value) || is_int($value)) {
            return $value;
        }

        return null;
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function formatMixed(mixed $value): string
    {
        if (is_float($value)) {
            return $this->formatNumber($value);
        }

        return (string) $value;
    }

    private function formatPercent(?float $value): string
    {
        if ($value === null) {
            return 'n/a';
        }

        return number_format($value, 2, '.', '').'%';
    }
}
