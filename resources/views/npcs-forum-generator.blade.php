@php
    use App\Enums\BaseItemRarity;
@endphp

@foreach ($npcs as $npc)
    <div>[center]
        <div>{{ $npc->name }}</div>
        <div>#base-npc.retro.{{ $npc->id }}</div>
        <br><br>

        @php
            $groupedLoots = $npc->loots->groupBy('rarity');
        @endphp

        @foreach ([BaseItemRarity::COMMON, BaseItemRarity::UNIQUE, BaseItemRarity::HEROIC, BaseItemRarity::LEGENDARY] as $rarity)
            @if ($groupedLoots->has($rarity->value))
                @switch($rarity)
                    @case(BaseItemRarity::COMMON)
                        <div>Zwykłe:</div>
                        @break
                    @case(BaseItemRarity::UNIQUE)
                        <div>Unikaty:</div>
                        @break
                    @case(BaseItemRarity::HEROIC)
                        <div>Heroiczne:</div>
                        @break
                    @case(BaseItemRarity::LEGENDARY)
                        <div>Legendarne:</div>
                        @break
                @endswitch

                @foreach ($groupedLoots[$rarity->value] as $loot)
                    #base-item.retro.{{ $loot->id }}
                @endforeach
                <br>
            @endif
        @endforeach

        @if ($npc->locations->isNotEmpty())
            @php
                $validLocations = $npc->locations->filter(fn($npcInstance) => $npcInstance->locations->isNotEmpty());
            @endphp

            @if ($validLocations->isNotEmpty())
                <br>
                <div>Respy:</div>
                @foreach ($validLocations as $npcInstance)
                    @foreach ($npcInstance->locations as $location)
                        <div>
                             {{ $location->map->name }} ({{ $location->map_id }}) ({{ $location->x }}, {{ $location->y }})
                        </div>
                    @endforeach
                @endforeach
            @endif
        @endif

        [/center]</div>
    <br><br>
@endforeach
