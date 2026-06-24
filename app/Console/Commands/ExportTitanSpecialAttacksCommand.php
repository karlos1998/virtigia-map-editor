<?php

namespace App\Console\Commands;

use App\Enums\BaseNpcRank;
use App\Models\BaseNpc;
use App\Models\DynamicModel;
use App\Models\MobSpecies;
use App\Models\SpecialAttack;
use App\Models\SpecialAttackDamage;
use App\Models\SpecialAttackEffect;
use App\Services\WorldTemplateConnectionResolver;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JsonException;
use Throwable;

class ExportTitanSpecialAttacksCommand extends Command
{
    protected $signature = 'titans:special-attacks
        {world? : World slug. Defaults to the configured world template}
        {--compact : Output compact JSON instead of pretty JSON}';

    protected $description = 'Export titan special attacks with damages, effects, and assigned mobs as JSON';

    public function handle(WorldTemplateConnectionResolver $connectionResolver): int
    {
        $worldConnection = $this->resolveWorldConnection($connectionResolver);

        if ($worldConnection === null) {
            return self::FAILURE;
        }

        [$world, $connection] = $worldConnection;

        DynamicModel::setGlobalConnection($connection);

        try {
            $attacks = $this->getTitanSpecialAttacks();
            $payload = [
                'world' => $world,
                'connection' => $connection,
                'generated_at' => now()->toIso8601String(),
                'filters' => [
                    'rank' => $this->enumToArray(BaseNpcRank::TITAN),
                ],
                'summary' => [
                    'titan_count' => BaseNpc::query()
                        ->where('rank', BaseNpcRank::TITAN->value)
                        ->count(),
                    'special_attack_count' => $attacks->count(),
                    'assignment_count' => $attacks->sum(fn (SpecialAttack $specialAttack): int => $specialAttack->baseNpcs->count()),
                ],
                'attacks' => $attacks
                    ->map(fn (SpecialAttack $specialAttack): array => $this->specialAttackToArray($specialAttack))
                    ->values()
                    ->all(),
            ];

            $this->output->writeln($this->encodePayload($payload));
        } catch (Throwable $throwable) {
            $this->error('Could not export titan special attacks: '.$throwable->getMessage());

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
        $world = (string) ($this->argument('world') ?: $connectionResolver->defaultWorldSlug());
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

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SpecialAttack>
     */
    private function getTitanSpecialAttacks(): \Illuminate\Database\Eloquent\Collection
    {
        return SpecialAttack::query()
            ->whereHas('baseNpcs', function (Builder $query): void {
                $query->where('rank', BaseNpcRank::TITAN->value);
            })
            ->with([
                'effects' => function (HasMany $query): void {
                    $query->orderBy('id');
                },
                'damages' => function (HasMany $query): void {
                    $query->orderBy('id');
                },
                'baseNpcs' => function (BelongsToMany $query): void {
                    $query
                        ->where('rank', BaseNpcRank::TITAN->value)
                        ->with('mobSpecies')
                        ->withPivot(['created_at', 'updated_at'])
                        ->orderBy('lvl')
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function specialAttackToArray(SpecialAttack $specialAttack): array
    {
        return [
            'id' => $specialAttack->id,
            'name' => $specialAttack->name,
            'attack_type' => $this->enumToArray($specialAttack->attack_type),
            'charge_turns' => $specialAttack->charge_turns,
            'target' => $this->enumToArray($specialAttack->target),
            'random_target' => $specialAttack->random_target,
            'damages' => $specialAttack->damages
                ->map(fn (SpecialAttackDamage $damage): array => $this->damageToArray($damage))
                ->values()
                ->all(),
            'effects' => $specialAttack->effects
                ->map(fn (SpecialAttackEffect $effect): array => $this->effectToArray($effect))
                ->values()
                ->all(),
            'assigned_titans_count' => $specialAttack->baseNpcs->count(),
            'assigned_titans' => $specialAttack->baseNpcs
                ->map(fn (BaseNpc $baseNpc): array => $this->baseNpcToArray($baseNpc))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function damageToArray(SpecialAttackDamage $damage): array
    {
        return [
            'id' => $damage->id,
            'element' => $this->enumToArray($damage->element),
            'min_damage' => $damage->min_damage,
            'max_damage' => $damage->max_damage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function effectToArray(SpecialAttackEffect $effect): array
    {
        return [
            'id' => $effect->id,
            'type' => $this->enumToArray($effect->type),
            'value' => $effect->value,
            'duration' => $effect->duration,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseNpcToArray(BaseNpc $baseNpc): array
    {
        return [
            'id' => $baseNpc->id,
            'name' => $baseNpc->name,
            'level' => $baseNpc->lvl,
            'rank' => $this->enumToArray($baseNpc->rank),
            'category' => $this->enumToArray($baseNpc->category),
            'profession' => $this->enumToArray($baseNpc->profession),
            'src' => $baseNpc->src,
            'type' => $baseNpc->type,
            'wt' => $baseNpc->wt,
            'mob_species' => $baseNpc->mobSpecies
                ->map(fn (MobSpecies $mobSpecies): array => [
                    'id' => $mobSpecies->id,
                    'name' => $mobSpecies->name,
                ])
                ->values()
                ->all(),
            'assignment' => [
                'created_at' => $this->formatTimestamp($baseNpc->pivot?->created_at ?? null),
                'updated_at' => $this->formatTimestamp($baseNpc->pivot?->updated_at ?? null),
            ],
        ];
    }

    /**
     * @return array{value: int|string, label: string}|null
     */
    private function enumToArray(?BackedEnum $enum): ?array
    {
        if ($enum === null) {
            return null;
        }

        return [
            'value' => $enum->value,
            'label' => method_exists($enum, 'description') ? $enum->description() : $enum->name,
        ];
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    private function encodePayload(array $payload): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        if (! $this->option('compact')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($payload, $flags);
    }
}
