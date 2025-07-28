<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcLocationResource} from "@/Resources/NpcLocation.resource";
import BaseNpcLocationsTable from "@/Pages/BaseNpc/Partials/BaseNpcLocationsTable.vue";
import {route} from "ziggy-js";
import ItemHeader from "@/Components/ItemHeader.vue";
import RemoveBaseNpc from "@/Pages/BaseNpc/Partials/RemoveBaseNpc.vue";
import MergeBaseNpc from "@/Pages/BaseNpc/Partials/MergeBaseNpc.vue";
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import {Link} from "@inertiajs/vue3";
import EditBaseNpcDialog from "@/Pages/BaseNpc/Components/EditBaseNpcDialog.vue";
import {ref} from "vue";
import {BaseNpcWithLoots} from "../../Resources/BaseNpc.resource";
import BaseNpcLootsTable from "./Partials/BaseNpcLootsTable.vue";
import EditBaseNpcSrcDialog from "./Components/EditBaseNpcSrcDialog.vue";
import BaseNpcActivityLogsTable from "./Partials/BaseNpcActivityLogsTable.vue";

defineProps<{
    baseNpc: BaseNpcWithLoots
    locations: NpcLocationResource[]
    logs?: any[]
}>()

const isEditBaseNpcDialogVisible = ref(false );
const isEditSrcVisible = ref(false );
</script>
<template>
    <AppLayout>

        <EditBaseNpcDialog :baseNpc v-model:visible="isEditBaseNpcDialogVisible" />

        <EditBaseNpcSrcDialog :baseNpc v-model:visible="isEditSrcVisible" />

        <ItemHeader
            :route-back="route('base-npcs.index')"
        >
            <template #header>
                <img v-tooltip="baseNpc.src" :src="baseNpc.src"  alt=""/>
                #{{ baseNpc.id }} - {{ baseNpc.name }}
            </template>
            <template #right-buttons>
                <Button @click="isEditBaseNpcDialogVisible = true" label="Edytuj" />
                <Button class="mx-2" @click="isEditSrcVisible = true" label="Zmień grafikę" />
            </template>
        </ItemHeader>

        <Message class="mb-8" severity="contrast">
            <div>Przeglądasz bazowgo NPC. Jest to najwyższy model NPC, któremu przydziela się nazwę, grafikę, czy statystyki do prowadzenia walk. Takiego bazowego NPC można umieścić w dowolnej ilości w dowolnych miejsicach na mapie np. jak Króliki. Jeśli widzisz na liście wiele lokalizacji tego NPC znaczy, że on jest w tych wszystkich miejscach na raz.</div>
        </Message>

        <DetailsCardList title="Informacje Podstawowe" >
            <DetailsCardListItem label="Nazwa" :value="baseNpc.name" />
            <DetailsCardListItem label="Link do grafiki" :value="baseNpc.src" />
            <DetailsCardListItem label="Lvl" :value="baseNpc.lvl" />
            <DetailsCardListItem label="Ranga">
                <template #value>
                    <Tag v-if="baseNpc.rank == 'NORMAL'" severity="info" value="Zwykły" />
                    <Tag v-else-if="baseNpc.rank == 'ELITE'" severity="success" value="Elita" />
                    <Tag v-else-if="baseNpc.rank == 'ELITE_II'" severity="warn" value="Elita II" />
                    <Tag v-else-if="baseNpc.rank == 'ELITE_III'" severity="warn" value="Elita III" />
                    <Tag v-else-if="baseNpc.rank == 'HERO'" severity="danger" value="Heros" />
                    <Tag v-else-if="baseNpc.rank == 'TITAN'" severity="contrast" value="Tytan" />
                </template>
            </DetailsCardListItem>
<!--            <DetailsCardListItem label="Type" :value="baseNpc.type" />-->
            <DetailsCardListItem label="Kategoria">
                <template #value>
                    <Tag v-if="baseNpc.category == 'MOB'" severity="info" value="MOB" />
                    <Tag v-else-if="baseNpc.category == 'NPC'" severity="secendary" value="NPC" />
                    <Tag v-else value="Nieznany rodzaj" />
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Profesja" :value="baseNpc.profession_name" />
            <DetailsCardListItem label="Agresywny">
                <template #value>
                    <Tag v-if="baseNpc.is_aggressive" severity="danger" value="Tak" />
                    <Tag v-else severity="success" value="Nie" />
                </template>
            </DetailsCardListItem>
        </DetailsCardList>

        <div class="card">
            <BaseNpcLootsTable  :base-npc />
        </div>

        <div class="card">
            <BaseNpcLocationsTable />
        </div>

        <BaseNpcActivityLogsTable v-if="logs" :logs="logs" :base-npc-id="baseNpc.id" />

        <MergeBaseNpc :baseNpc />

        <RemoveBaseNpc :baseNpc />

    </AppLayout>
</template>

<style scoped>

</style>
