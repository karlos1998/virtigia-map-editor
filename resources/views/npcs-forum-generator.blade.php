@php
    use App\Enums\BaseItemRarity;

    $world = session('world');
    $format = $format ?? 'bbcode';
    $rarityLabels = [
        BaseItemRarity::COMMON->value => 'Zwykłe',
        BaseItemRarity::UNIQUE->value => 'Unikaty',
        BaseItemRarity::HEROIC->value => 'Heroiczne',
        BaseItemRarity::LEGENDARY->value => 'Legendarne',
    ];
@endphp

<style>
    body {
        margin: 0;
        padding: 24px;
        background: #0f172a;
        color: #e2e8f0;
        font-family: Inter, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    pre {
        white-space: pre-wrap;
        word-break: break-word;
        margin: 0;
        padding: 24px;
        border-radius: 16px;
        border: 1px solid #334155;
        background: #020617;
        line-height: 1.7;
        font-size: 14px;
    }

    .toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 16px;
    }

    .copy-button {
        border: 1px solid #475569;
        background: #1e293b;
        color: #e2e8f0;
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .copy-button:hover {
        background: #334155;
        border-color: #64748b;
    }
</style>

<div class="toolbar">
    <button class="copy-button" type="button" onclick="copyTemplate()">Kopiuj</button>
</div>

<pre id="generator-output">@foreach ($npcs as $npc)
@php
    $groupedLoots = $npc->loots->groupBy('rarity');
    $validLocations = $npc->locations->filter(fn ($npcInstance) => $npcInstance->locations->isNotEmpty());
    $locationLines = $validLocations->flatMap(
        fn ($npcInstance) => $npcInstance->locations->map(
            fn ($location) => "**{$location->map->name}** ({$location->map_id}) — `({$location->x}, {$location->y})`"
        )
    );
@endphp
@if ($format === 'markdown')
| {{ $npc->name }} |
|:---:|
| `#base-npc.{{ $world }}.{{ $npc->id }}` |

@foreach ([BaseItemRarity::COMMON, BaseItemRarity::UNIQUE, BaseItemRarity::HEROIC, BaseItemRarity::LEGENDARY] as $rarity)
@if ($groupedLoots->has($rarity->value))
| **{{ $rarityLabels[$rarity->value] }}:** @foreach ($groupedLoots[$rarity->value] as $loot)`#base-item.{{ $world }}.{{ $loot->id }}`@if (! $loop->last) · @endif @endforeach |

@endif
@endforeach
@if ($locationLines->isNotEmpty())
| **Respy** |
@foreach ($locationLines as $locationLine)
| {{ $locationLine }} |
@endforeach

@endif

---

@else
[center]
{{ $npc->name }}
#base-npc.{{ $world }}.{{ $npc->id }}

@foreach ([BaseItemRarity::COMMON, BaseItemRarity::UNIQUE, BaseItemRarity::HEROIC, BaseItemRarity::LEGENDARY] as $rarity)
@if ($groupedLoots->has($rarity->value))
{{ $rarityLabels[$rarity->value] }}:
@foreach ($groupedLoots[$rarity->value] as $loot)
#base-item.{{ $world }}.{{ $loot->id }}
@endforeach

@endif
@endforeach
@if ($validLocations->isNotEmpty())
Respy:
@foreach ($validLocations as $npcInstance)
@foreach ($npcInstance->locations as $location)
{{ $location->map->name }} ({{ $location->map_id }}) ({{ $location->x }}, {{ $location->y }})
@endforeach
@endforeach
@endif
[/center]


@endif
@endforeach</pre>

<script>
    async function copyTemplate() {
        const output = document.getElementById('generator-output');

        if (!output) {
            return;
        }

        const button = document.querySelector('.copy-button');

        try {
            await navigator.clipboard.writeText(output.textContent ?? '');

            if (button) {
                const originalLabel = button.textContent;
                button.textContent = 'Skopiowano';

                setTimeout(() => {
                    button.textContent = originalLabel;
                }, 1500);
            }
        } catch (error) {
            console.error('Nie udało się skopiować treści generatora.', error);
        }
    }
</script>
