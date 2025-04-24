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

        [/center]</div>

        <br><br>

@endforeach
