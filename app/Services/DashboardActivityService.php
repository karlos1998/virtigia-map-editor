<?php

namespace App\Services;

use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Throwable;

final class DashboardActivityService
{
    private const SESSION_GAP_MINUTES = 45;

    private const SINGLE_ACTIVITY_MINUTES = 10;

    private const MAX_DAY_MINUTES = 720;

    private const WEEKDAYS = [
        1 => 'Pon',
        2 => 'Wt',
        3 => 'Śr',
        4 => 'Czw',
        5 => 'Pt',
        6 => 'Sob',
        7 => 'Nd',
    ];

    private const SUBJECTS = [
        \App\Models\Dialog::class => [
            'label' => 'Dialog',
            'area' => 'dialogs',
            'area_label' => 'Dialogi',
            'route' => 'dialogs.show',
            'name_column' => 'name',
        ],
        \App\Models\DialogNode::class => [
            'label' => 'Węzeł dialogu',
            'area' => 'dialogs',
            'area_label' => 'Dialogi',
            'name_column' => 'content',
        ],
        \App\Models\DialogNodeOption::class => [
            'label' => 'Opcja dialogu',
            'area' => 'dialogs',
            'area_label' => 'Dialogi',
            'name_column' => 'label',
        ],
        \App\Models\DialogEdge::class => [
            'label' => 'Połączenie dialogu',
            'area' => 'dialogs',
            'area_label' => 'Dialogi',
        ],
        \App\Models\Quest::class => [
            'label' => 'Quest',
            'area' => 'quests',
            'area_label' => 'Questy',
            'route' => 'quests.show',
            'name_column' => 'name',
        ],
        \App\Models\QuestStep::class => [
            'label' => 'Krok questa',
            'area' => 'quests',
            'area_label' => 'Questy',
            'route' => 'quest.steps.show',
            'name_column' => 'name',
        ],
        \App\Models\QuestStepAutoProgress::class => [
            'label' => 'Auto-progress questa',
            'area' => 'quests',
            'area_label' => 'Questy',
        ],
        \App\Models\BaseNpc::class => [
            'label' => 'Base NPC',
            'area' => 'npcs',
            'area_label' => 'NPC',
            'route' => 'base-npcs.show',
            'name_column' => 'name',
        ],
        \App\Models\Npc::class => [
            'label' => 'NPC na mapie',
            'area' => 'npcs',
            'area_label' => 'NPC',
            'route' => 'npcs.show',
        ],
        \App\Models\NpcLocation::class => [
            'label' => 'Pozycja NPC',
            'area' => 'npcs',
            'area_label' => 'NPC',
        ],
        \App\Models\NpcGroup::class => [
            'label' => 'Grupa NPC',
            'area' => 'npcs',
            'area_label' => 'NPC',
        ],
        \App\Models\BaseItem::class => [
            'label' => 'Przedmiot',
            'area' => 'items',
            'area_label' => 'Itemy i loot',
            'route' => 'base-items.show',
            'name_column' => 'name',
        ],
        \App\Models\BaseNpcLoot::class => [
            'label' => 'Loot NPC',
            'area' => 'items',
            'area_label' => 'Itemy i loot',
        ],
        \App\Models\Shop::class => [
            'label' => 'Sklep',
            'area' => 'shops',
            'area_label' => 'Sklepy',
            'route' => 'shops.show',
            'name_column' => 'name',
        ],
        \App\Models\ShopItem::class => [
            'label' => 'Item w sklepie',
            'area' => 'shops',
            'area_label' => 'Sklepy',
        ],
        \App\Models\Map::class => [
            'label' => 'Mapa',
            'area' => 'maps',
            'area_label' => 'Mapy i przejścia',
            'route' => 'maps.show',
            'name_column' => 'name',
        ],
        \App\Models\Door::class => [
            'label' => 'Przejście',
            'area' => 'maps',
            'area_label' => 'Mapy i przejścia',
        ],
        \App\Models\RespawnPoint::class => [
            'label' => 'Respawn',
            'area' => 'maps',
            'area_label' => 'Mapy i przejścia',
        ],
        \App\Models\SpawnPoint::class => [
            'label' => 'Spawn',
            'area' => 'maps',
            'area_label' => 'Mapy i przejścia',
        ],
        \App\Models\WorldMinimapNode::class => [
            'label' => 'Minimapa świata',
            'area' => 'maps',
            'area_label' => 'Mapy i przejścia',
        ],
        \App\Models\Hotel::class => [
            'label' => 'Hotel',
            'area' => 'hotels',
            'area_label' => 'Hotele',
            'route' => 'hotels.show',
            'name_column' => 'name',
        ],
        \App\Models\HotelRoom::class => [
            'label' => 'Pokój hotelu',
            'area' => 'hotels',
            'area_label' => 'Hotele',
            'name_column' => 'name',
        ],
        \App\Models\SeasonalEvent::class => [
            'label' => 'Event sezonowy',
            'area' => 'events',
            'area_label' => 'Eventy',
            'route' => 'seasonal-events.show',
            'name_column' => 'name',
        ],
        \App\Models\MobSpecies::class => [
            'label' => 'Gatunek moba',
            'area' => 'npcs',
            'area_label' => 'NPC',
            'route' => 'mob-species.show',
            'name_column' => 'name',
        ],
        \App\Models\SpecialAttack::class => [
            'label' => 'Atak specjalny',
            'area' => 'npcs',
            'area_label' => 'NPC',
            'route' => 'special-attacks.show',
            'name_column' => 'name',
        ],
        \App\Models\Audio::class => [
            'label' => 'Audio',
            'area' => 'maps',
            'area_label' => 'Mapy i przejścia',
            'route' => 'audios.show',
            'name_column' => 'name',
        ],
        \App\Models\Book::class => [
            'label' => 'Książka',
            'area' => 'items',
            'area_label' => 'Itemy i loot',
            'route' => 'books.show',
            'name_column' => 'name',
        ],
    ];

    public function __construct(private readonly Activity $activityModel) {}

    /**
     * @return array<string, mixed>
     */
    public function getAnalytics(int $days, ?string $world): array
    {
        $to = now();
        $from = now()->subDays($days - 1)->startOfDay();

        $activities = $this->activityModel
            ->newQuery()
            ->with('causer')
            ->where('created_at', '>=', $from)
            ->when($world !== null, fn ($query) => $query->where('world', $world))
            ->orderBy('created_at')
            ->get([
                'id',
                'description',
                'event',
                'subject_type',
                'subject_id',
                'causer_type',
                'causer_id',
                'properties',
                'created_at',
                'world',
            ]);

        $subjectNames = $this->resolveSubjectNames($activities);
        $enrichedActivities = $activities->map(fn (Activity $activity): array => $this->enrichActivity($activity, $subjectNames));
        $workStats = $this->calculateWorkStats($activities);
        $dailyActivity = $this->buildDailyActivity($activities, $from, $to, $workStats['daily_minutes']);
        $focusObjects = $this->buildFocusObjects($enrichedActivities);
        $attachmentFeed = $this->buildAttachmentFeed($activities, $subjectNames);

        return [
            'filters' => [
                'days' => $days,
                'world' => $world,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'period_options' => [7, 14, 30, 90],
            ],
            'summary' => $this->buildSummary($activities, $dailyActivity, $workStats, $focusObjects),
            'dailyActivity' => $dailyActivity,
            'hourlyActivity' => $this->buildHourlyActivity($activities),
            'workHeatmap' => $this->buildWorkHeatmap($activities),
            'activityAreas' => $this->buildActivityAreas($enrichedActivities),
            'eventBreakdown' => $this->buildEventBreakdown($enrichedActivities),
            'userActivity' => $this->buildUserActivity($enrichedActivities, $workStats),
            'focusObjects' => array_slice($focusObjects, 0, 12),
            'questFocus' => $this->filterFocusObjects($focusObjects, ['quests']),
            'npcFocus' => $this->filterFocusObjects($focusObjects, ['npcs']),
            'attachmentFeed' => $attachmentFeed,
            'attachmentSummary' => $this->buildAttachmentSummary($attachmentFeed),
            'recentActivities' => $enrichedActivities
                ->sortByDesc('created_at_timestamp')
                ->take(12)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<string, array<int, string>>
     */
    private function resolveSubjectNames(Collection $activities): array
    {
        $names = [];

        $activities
            ->filter(fn (Activity $activity): bool => filled($activity->subject_type) && filled($activity->subject_id))
            ->groupBy('subject_type')
            ->each(function (Collection $subjectActivities, string $subjectType) use (&$names): void {
                $nameColumn = self::SUBJECTS[$subjectType]['name_column'] ?? null;

                if (! $nameColumn || ! class_exists($subjectType)) {
                    return;
                }

                $ids = $subjectActivities
                    ->pluck('subject_id')
                    ->filter()
                    ->unique()
                    ->values();

                if ($ids->isEmpty()) {
                    return;
                }

                try {
                    $subjectType::query()
                        ->whereIn('id', $ids)
                        ->get(['id', $nameColumn])
                        ->each(function ($model) use (&$names, $subjectType, $nameColumn): void {
                            $name = (string) ($model->{$nameColumn} ?? '');

                            if ($name === '') {
                                return;
                            }

                            $names[$subjectType][(int) $model->id] = Str::limit($name, 90);
                        });
                } catch (Throwable) {
                    return;
                }
            });

        return $names;
    }

    /**
     * @param  array<string, array<int, string>>  $subjectNames
     * @return array<string, mixed>
     */
    private function enrichActivity(Activity $activity, array $subjectNames): array
    {
        $classification = $this->classifyActivity($activity);
        $event = $activity->event ?: $activity->description;
        $subjectLabel = $this->subjectLabel($activity->subject_type);
        $subjectName = $this->resolveActivitySubjectName($activity, $subjectNames);
        $routeName = $this->subjectRouteName($activity->subject_type);

        return [
            'id' => $activity->id,
            'event' => $event,
            'event_label' => $this->eventLabel($event),
            'event_severity' => $this->eventSeverity($event),
            'action_label' => $this->actionGroup($event),
            'description' => $activity->description,
            'summary' => $this->activitySummary($activity, $subjectLabel, $subjectName, $event),
            'subject_type' => $activity->subject_type,
            'subject_label' => $subjectLabel,
            'subject_id' => $activity->subject_id,
            'subject_name' => $subjectName,
            'route_name' => $routeName,
            'route_param' => $routeName && $activity->subject_id ? $activity->subject_id : null,
            'area' => $classification['area'],
            'area_label' => $classification['area_label'],
            'causer_id' => $activity->causer_id,
            'causer_name' => $activity->causer?->name ?? 'System',
            'created_at' => $activity->created_at?->toIso8601String(),
            'created_at_label' => $activity->created_at?->format('Y-m-d H:i'),
            'created_at_timestamp' => $activity->created_at?->getTimestamp() ?? 0,
        ];
    }

    /**
     * @param  array<string, array<int, string>>  $subjectNames
     */
    private function resolveActivitySubjectName(Activity $activity, array $subjectNames): string
    {
        $subjectType = $activity->subject_type;
        $subjectId = $activity->subject_id;

        $value = $this->firstFilledProperty($activity->properties, [
            'attributes.name',
            'attributes.label',
            'attributes.content',
            'old.name',
            'old.label',
            'old.content',
        ]);

        if ($value !== null) {
            return Str::limit((string) $value, 90);
        }

        if ($subjectType && $subjectId && isset($subjectNames[$subjectType][(int) $subjectId])) {
            return $subjectNames[$subjectType][(int) $subjectId];
        }

        return $subjectId ? '#'.$subjectId : 'Brak obiektu';
    }

    /**
     * @return array{area: string, area_label: string}
     */
    private function classifyActivity(Activity $activity): array
    {
        $event = (string) $activity->event;

        if (str_contains($event, 'loot') || str_contains($event, 'item-to-shop') || str_contains($event, 'shop-item')) {
            return [
                'area' => 'items',
                'area_label' => 'Itemy i loot',
            ];
        }

        $subjectConfig = self::SUBJECTS[$activity->subject_type] ?? null;

        if ($subjectConfig) {
            return [
                'area' => $subjectConfig['area'],
                'area_label' => $subjectConfig['area_label'],
            ];
        }

        return [
            'area' => 'other',
            'area_label' => 'Inne',
        ];
    }

    private function subjectLabel(?string $subjectType): string
    {
        if (! $subjectType) {
            return 'System';
        }

        return self::SUBJECTS[$subjectType]['label'] ?? class_basename($subjectType);
    }

    private function subjectRouteName(?string $subjectType): ?string
    {
        if (! $subjectType) {
            return null;
        }

        return self::SUBJECTS[$subjectType]['route'] ?? null;
    }

    private function eventLabel(?string $event): string
    {
        return match ($event) {
            'created' => 'Utworzono',
            'updated' => 'Zaktualizowano',
            'deleted' => 'Usunięto',
            'attach-base-npc-loots', 'attach-to-base-npc-loots' => 'Dodano loot',
            'detach-base-npc-loots', 'detach-from-base-npc-loots' => 'Usunięto loot',
            'attach-item-to-shop', 'shop-item-attached' => 'Dodano item do sklepu',
            'detach-item-from-shop', 'shop-item-detach' => 'Usunięto item ze sklepu',
            'transfer-npc' => 'Przeniesiono NPC',
            'bulk-update-base-npc-guaranteed-loot' => 'Masowo ustawiono loot',
            'update-base-npc-guaranteed-loot' => 'Zmieniono gwarantowany loot',
            'bulk-update-base-item-descriptions' => 'Masowo poprawiono opisy',
            default => $event ? Str::headline(str_replace('-', ' ', $event)) : 'Aktywność',
        };
    }

    private function eventSeverity(?string $event): string
    {
        if ($event === 'created' || str_starts_with((string) $event, 'attach')) {
            return 'success';
        }

        if ($event === 'deleted' || str_starts_with((string) $event, 'detach')) {
            return 'danger';
        }

        if ($event === 'updated' || str_contains((string) $event, 'bulk') || str_contains((string) $event, 'transfer')) {
            return 'info';
        }

        return 'secondary';
    }

    private function actionGroup(?string $event): string
    {
        $event = (string) $event;

        if ($event === 'created') {
            return 'Tworzenie';
        }

        if ($event === 'updated' || str_starts_with($event, 'update')) {
            return 'Edycja';
        }

        if ($event === 'deleted') {
            return 'Usuwanie';
        }

        if (str_starts_with($event, 'attach')) {
            return 'Podpinanie';
        }

        if (str_starts_with($event, 'detach')) {
            return 'Odpinanie';
        }

        if (str_contains($event, 'bulk')) {
            return 'Operacje masowe';
        }

        if (str_contains($event, 'transfer')) {
            return 'Transfery';
        }

        return 'Inne';
    }

    private function activitySummary(Activity $activity, string $subjectLabel, string $subjectName, ?string $event): string
    {
        $properties = $activity->properties;

        if ($event === 'attach-base-npc-loots') {
            $itemName = $this->firstFilledProperty($properties, ['base_item.name', 'base_item.attributes.name']) ?? 'przedmiot';

            return "Dodano {$itemName} do lootów {$subjectName}";
        }

        if ($event === 'detach-base-npc-loots') {
            $itemName = $this->firstFilledProperty($properties, ['base_item.name', 'base_item.attributes.name']) ?? 'przedmiot';

            return "Usunięto {$itemName} z lootów {$subjectName}";
        }

        if ($event === 'attach-item-to-shop') {
            $itemName = $this->firstFilledProperty($properties, ['base_item.name', 'base_item.attributes.name']) ?? 'przedmiot';

            return "Dodano {$itemName} do sklepu {$subjectName}";
        }

        if ($event === 'detach-item-from-shop') {
            $itemName = $this->firstFilledProperty($properties, ['base_item.name', 'base_item.attributes.name']) ?? 'przedmiot';

            return "Usunięto {$itemName} ze sklepu {$subjectName}";
        }

        return trim($this->eventLabel($event).' '.$subjectLabel.' '.$subjectName);
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array{users: array<int, array<string, mixed>>, daily_minutes: array<string, int>, total_minutes: int, total_sessions: int}
     */
    private function calculateWorkStats(Collection $activities): array
    {
        $users = [];
        $dailyMinutes = [];
        $totalMinutes = 0;
        $totalSessions = 0;

        $activities
            ->filter(fn (Activity $activity): bool => filled($activity->causer_id))
            ->groupBy('causer_id')
            ->each(function (Collection $userActivities, int|string $causerId) use (&$users, &$dailyMinutes, &$totalMinutes, &$totalSessions): void {
                $minutes = 0;
                $sessions = 0;

                $userActivities
                    ->groupBy(fn (Activity $activity): string => $activity->created_at->format('Y-m-d'))
                    ->each(function (Collection $dayActivities, string $date) use (&$minutes, &$sessions, &$dailyMinutes): void {
                        $dayStats = $this->estimateDayWork($dayActivities);

                        $minutes += $dayStats['minutes'];
                        $sessions += $dayStats['sessions'];
                        $dailyMinutes[$date] = ($dailyMinutes[$date] ?? 0) + $dayStats['minutes'];
                    });

                $sortedActivities = $userActivities->sortBy('created_at')->values();
                $firstActivity = $sortedActivities->first();
                $lastActivity = $sortedActivities->last();

                $users[(int) $causerId] = [
                    'id' => (int) $causerId,
                    'name' => $lastActivity?->causer?->name ?? 'Nieznany użytkownik',
                    'activities' => $userActivities->count(),
                    'active_days' => $userActivities
                        ->map(fn (Activity $activity): string => $activity->created_at->format('Y-m-d'))
                        ->unique()
                        ->count(),
                    'estimated_minutes' => $minutes,
                    'estimated_hours' => round($minutes / 60, 1),
                    'sessions' => $sessions,
                    'first_activity_at' => $firstActivity?->created_at?->toIso8601String(),
                    'last_activity_at' => $lastActivity?->created_at?->toIso8601String(),
                    'last_activity_label' => $lastActivity?->created_at?->format('Y-m-d H:i'),
                ];

                $totalMinutes += $minutes;
                $totalSessions += $sessions;
            });

        return [
            'users' => $users,
            'daily_minutes' => $dailyMinutes,
            'total_minutes' => $totalMinutes,
            'total_sessions' => $totalSessions,
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array{minutes: int, sessions: int}
     */
    private function estimateDayWork(Collection $activities): array
    {
        $sortedActivities = $activities->sortBy('created_at')->values();

        if ($sortedActivities->isEmpty()) {
            return [
                'minutes' => 0,
                'sessions' => 0,
            ];
        }

        $minutes = self::SINGLE_ACTIVITY_MINUTES;
        $sessions = 1;
        $previousActivity = $sortedActivities->first();

        foreach ($sortedActivities->slice(1) as $activity) {
            $gapMinutes = (int) $previousActivity->created_at->diffInMinutes($activity->created_at);

            if ($gapMinutes > self::SESSION_GAP_MINUTES) {
                $sessions++;
                $minutes += self::SINGLE_ACTIVITY_MINUTES;
            } else {
                $minutes += max(1, $gapMinutes);
            }

            $previousActivity = $activity;
        }

        return [
            'minutes' => min($minutes, self::MAX_DAY_MINUTES),
            'sessions' => $sessions,
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @param  array<string, int>  $dailyMinutes
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyActivity(Collection $activities, mixed $from, mixed $to, array $dailyMinutes): array
    {
        $activitiesByDay = $activities->groupBy(fn (Activity $activity): string => $activity->created_at->format('Y-m-d'));

        return collect(CarbonPeriod::create($from, '1 day', $to))
            ->map(function ($date) use ($activitiesByDay, $dailyMinutes): array {
                $key = $date->format('Y-m-d');
                $dayActivities = $activitiesByDay->get($key, collect());

                return [
                    'date' => $key,
                    'label' => $date->format('d.m'),
                    'count' => $dayActivities->count(),
                    'active_users' => $dayActivities->pluck('causer_id')->filter()->unique()->count(),
                    'estimated_hours' => round(($dailyMinutes[$key] ?? 0) / 60, 1),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<int, array{hour: int, label: string, count: int}>
     */
    private function buildHourlyActivity(Collection $activities): array
    {
        $counts = $activities
            ->groupBy(fn (Activity $activity): int => (int) $activity->created_at->format('G'))
            ->map(fn (Collection $hourActivities): int => $hourActivities->count());

        return collect(range(0, 23))
            ->map(fn (int $hour): array => [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'count' => $counts->get($hour, 0),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array{max: int, hours: array<int, int>, rows: array<int, array<string, mixed>>}
     */
    private function buildWorkHeatmap(Collection $activities): array
    {
        $counts = [];

        foreach ($activities as $activity) {
            $weekday = $activity->created_at->isoWeekday();
            $hour = (int) $activity->created_at->format('G');
            $counts[$weekday][$hour] = ($counts[$weekday][$hour] ?? 0) + 1;
        }

        $max = collect($counts)
            ->flatMap(fn (array $hours): array => $hours)
            ->max() ?? 0;

        $hours = range(0, 23);

        return [
            'max' => (int) $max,
            'hours' => $hours,
            'rows' => collect(self::WEEKDAYS)
                ->map(fn (string $weekdayLabel, int $weekday): array => [
                    'weekday' => $weekday,
                    'label' => $weekdayLabel,
                    'cells' => collect($hours)
                        ->map(fn (int $hour): array => [
                            'hour' => $hour,
                            'count' => $counts[$weekday][$hour] ?? 0,
                            'intensity' => $max > 0 ? round((($counts[$weekday][$hour] ?? 0) / $max) * 100) : 0,
                        ])
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $activities
     * @return array<int, array<string, mixed>>
     */
    private function buildActivityAreas(Collection $activities): array
    {
        return $activities
            ->groupBy('area')
            ->map(fn (Collection $areaActivities): array => [
                'area' => $areaActivities->first()['area'],
                'label' => $areaActivities->first()['area_label'],
                'count' => $areaActivities->count(),
                'users_count' => $areaActivities->pluck('causer_id')->filter()->unique()->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $activities
     * @return array<int, array<string, mixed>>
     */
    private function buildEventBreakdown(Collection $activities): array
    {
        return $activities
            ->groupBy('event')
            ->map(fn (Collection $eventActivities): array => [
                'event' => $eventActivities->first()['event'],
                'label' => $eventActivities->first()['event_label'],
                'action_label' => $eventActivities->first()['action_label'],
                'severity' => $eventActivities->first()['event_severity'],
                'count' => $eventActivities->count(),
            ])
            ->sortByDesc('count')
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $activities
     * @param  array{users: array<int, array<string, mixed>>, daily_minutes: array<string, int>, total_minutes: int, total_sessions: int}  $workStats
     * @return array<int, array<string, mixed>>
     */
    private function buildUserActivity(Collection $activities, array $workStats): array
    {
        $activitiesByUser = $activities
            ->filter(fn (array $activity): bool => filled($activity['causer_id']))
            ->groupBy('causer_id');

        $totalActivities = max(1, $activities->count());

        return collect($workStats['users'])
            ->map(function (array $userStats) use ($activitiesByUser, $totalActivities): array {
                $userActivities = $activitiesByUser->get($userStats['id'], collect());

                $dominantArea = $userActivities
                    ->groupBy('area_label')
                    ->map(fn (Collection $items, string $label): array => [
                        'label' => $label,
                        'count' => $items->count(),
                    ])
                    ->sortByDesc('count')
                    ->first();

                $dominantAction = $userActivities
                    ->groupBy('action_label')
                    ->map(fn (Collection $items, string $label): array => [
                        'label' => $label,
                        'count' => $items->count(),
                    ])
                    ->sortByDesc('count')
                    ->first();

                return [
                    ...$userStats,
                    'share' => round(($userStats['activities'] / $totalActivities) * 100, 1),
                    'dominant_area' => $dominantArea['label'] ?? 'Brak',
                    'dominant_action' => $dominantAction['label'] ?? 'Brak',
                    'route_name' => 'users.show',
                    'route_param' => $userStats['id'],
                ];
            })
            ->sortByDesc('activities')
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $activities
     * @return array<int, array<string, mixed>>
     */
    private function buildFocusObjects(Collection $activities): array
    {
        return $activities
            ->filter(fn (array $activity): bool => filled($activity['subject_type']) && filled($activity['subject_id']))
            ->groupBy(fn (array $activity): string => $activity['subject_type'].'#'.$activity['subject_id'])
            ->map(function (Collection $objectActivities): array {
                $latestActivity = $objectActivities->sortByDesc('created_at_timestamp')->first();
                $firstActivity = $objectActivities->first();

                return [
                    'subject_type' => $firstActivity['subject_type'],
                    'subject_label' => $firstActivity['subject_label'],
                    'subject_id' => $firstActivity['subject_id'],
                    'subject_name' => $firstActivity['subject_name'],
                    'area' => $firstActivity['area'],
                    'area_label' => $firstActivity['area_label'],
                    'route_name' => $firstActivity['route_name'],
                    'route_param' => $firstActivity['route_param'],
                    'count' => $objectActivities->count(),
                    'users_count' => $objectActivities->pluck('causer_id')->filter()->unique()->count(),
                    'last_event_label' => $latestActivity['event_label'],
                    'last_activity_at' => $latestActivity['created_at'],
                    'last_activity_label' => $latestActivity['created_at_label'],
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $focusObjects
     * @param  array<int, string>  $areas
     * @return array<int, array<string, mixed>>
     */
    private function filterFocusObjects(array $focusObjects, array $areas): array
    {
        return collect($focusObjects)
            ->filter(fn (array $object): bool => in_array($object['area'], $areas, true))
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @param  array<string, array<int, string>>  $subjectNames
     * @return array<int, array<string, mixed>>
     */
    private function buildAttachmentFeed(Collection $activities, array $subjectNames): array
    {
        return $activities
            ->filter(fn (Activity $activity): bool => in_array($activity->event, [
                'attach-base-npc-loots',
                'detach-base-npc-loots',
                'attach-item-to-shop',
                'detach-item-from-shop',
            ], true))
            ->sortByDesc('created_at')
            ->take(12)
            ->map(function (Activity $activity) use ($subjectNames): array {
                $isShopEvent = in_array($activity->event, ['attach-item-to-shop', 'detach-item-from-shop'], true);
                $isDetachEvent = str_starts_with((string) $activity->event, 'detach');
                $targetRouteName = $isShopEvent ? 'shops.show' : 'base-npcs.show';
                $targetType = $isShopEvent ? \App\Models\Shop::class : \App\Models\BaseNpc::class;
                $targetLabel = $isShopEvent ? 'Sklep' : 'Base NPC';
                $targetName = $subjectNames[$targetType][(int) $activity->subject_id]
                    ?? ($activity->subject_id ? "{$targetLabel} #{$activity->subject_id}" : $targetLabel);
                $itemId = $this->firstFilledProperty($activity->properties, ['base_item.id', 'base_item.attributes.id']);
                $itemName = $this->firstFilledProperty($activity->properties, ['base_item.name', 'base_item.attributes.name'])
                    ?? ($itemId ? "Przedmiot #{$itemId}" : 'Przedmiot');

                return [
                    'id' => $activity->id,
                    'event' => $activity->event,
                    'action' => $isDetachEvent ? 'detach' : 'attach',
                    'action_label' => $isDetachEvent ? 'Odpięto' : 'Podpięto',
                    'severity' => $isDetachEvent ? 'danger' : 'success',
                    'item_id' => $itemId ? (int) $itemId : null,
                    'item_name' => $itemName,
                    'item_route_name' => $itemId ? 'base-items.show' : null,
                    'item_route_param' => $itemId ? (int) $itemId : null,
                    'target_id' => $activity->subject_id,
                    'target_name' => $targetName,
                    'target_label' => $targetLabel,
                    'target_route_name' => $activity->subject_id ? $targetRouteName : null,
                    'target_route_param' => $activity->subject_id,
                    'position' => $this->firstFilledProperty($activity->properties, ['position']),
                    'causer_name' => $activity->causer?->name ?? 'System',
                    'created_at' => $activity->created_at?->toIso8601String(),
                    'created_at_label' => $activity->created_at?->format('Y-m-d H:i'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachmentFeed
     * @return array<int, array<string, mixed>>
     */
    private function buildAttachmentSummary(array $attachmentFeed): array
    {
        return collect($attachmentFeed)
            ->groupBy(fn (array $item): string => $item['target_label'].'|'.$item['action_label'])
            ->map(fn (Collection $items): array => [
                'label' => $items->first()['target_label'],
                'action_label' => $items->first()['action_label'],
                'severity' => $items->first()['severity'],
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @param  array<int, array<string, mixed>>  $dailyActivity
     * @param  array{users: array<int, array<string, mixed>>, daily_minutes: array<string, int>, total_minutes: int, total_sessions: int}  $workStats
     * @param  array<int, array<string, mixed>>  $focusObjects
     * @return array<string, mixed>
     */
    private function buildSummary(Collection $activities, array $dailyActivity, array $workStats, array $focusObjects): array
    {
        $lastActivity = $activities->sortByDesc('created_at')->first();
        $busiestDay = collect($dailyActivity)->sortByDesc('count')->first();
        $topUser = collect($workStats['users'])->sortByDesc('activities')->first();

        return [
            'total_activities' => $activities->count(),
            'active_users' => $activities->pluck('causer_id')->filter()->unique()->count(),
            'estimated_hours' => round($workStats['total_minutes'] / 60, 1),
            'work_sessions' => $workStats['total_sessions'],
            'touched_objects' => count($focusObjects),
            'last_activity_at' => $lastActivity?->created_at?->toIso8601String(),
            'last_activity_label' => $lastActivity?->created_at?->format('Y-m-d H:i'),
            'busiest_day' => $busiestDay,
            'top_user' => $topUser,
        ];
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function firstFilledProperty(mixed $properties, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($properties, $path);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
