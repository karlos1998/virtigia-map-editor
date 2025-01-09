<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcLocationResource} from "@/Resources/NpcLocation.resource";
import BaseNpcLocationsTable from "@/Pages/BaseNpc/Partials/BaseNpcLocationsTable.vue";
import {route} from "ziggy-js";
import ItemHeader from "@/Components/ItemHeader.vue";
import RemoveBaseNpc from "@/Pages/BaseNpc/Partials/RemoveBaseNpc.vue";
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import {Link} from "@inertiajs/vue3";

defineProps<{
    baseNpc: BaseNpcResource
    locations: NpcLocationResource[]
}>()

</script>
<template>
    <AppLayout>

        <ItemHeader
            :route-back="route('base-npcs.index')"
        >
            <template #header>
                <img v-tooltip="baseNpc.src" :src="'https://s3.letscode.it/virtigia-assets/img/npc/' + baseNpc.src"  alt=""/>
                #{{ baseNpc.id }} - {{ baseNpc.name }}
            </template>
        </ItemHeader>

        <Message class="mb-8" severity="contrast">
            <div>Przeglądasz bazowgo NPC. Jest to najwyższy model NPC, któremu przydziela się nazwę, grafikę, czy statystyki do prowadzenia walk. Takiego bazowego NPC można umieścić w dowolnej ilości w dowolnych miejsicach na mapie np. jak Króliki. Jeśli widzisz na liście wiele lokalizacji tego NPC znaczy, że on jest w tych wszystkich miejscach na raz.</div>
        </Message>

        <DetailsCardList title="Informacje Podstawowe" >
            <DetailsCardListItem label="Nazwa" :value="baseNpc.name" />
            <DetailsCardListItem label="Link do grafiki" :value="'https://s3.letscode.it/virtigia-assets/img/npc/' + baseNpc.src" />
            <DetailsCardListItem label="Lvl" :value="baseNpc.lvl" />
            <DetailsCardListItem label="Type" :value="baseNpc.type" />
            <DetailsCardListItem label="Kategoria">
                <template #value>
                    <Tag v-if="baseNpc.category == 'MOB'" severity="info" value="MOB" />
                    <Tag v-else-if="baseNpc.category == 'NPC'" severity="secendary" value="NPC" />
                    <Tag v-else value="Nieznany rodzaj" />
                </template>
            </DetailsCardListItem>
        </DetailsCardList>

        <div class="card">
            <BaseNpcLocationsTable />
        </div>

        <RemoveBaseNpc :baseNpc />

    </AppLayout>
</template>

<style scoped>

</style>
