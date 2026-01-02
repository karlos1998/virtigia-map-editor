<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcResource, NpcWithDetails} from "@/Resources/Npc.resource";
import {route} from "ziggy-js";
import {router} from "@inertiajs/vue3";
import ItemHeader from "@/Components/ItemHeader.vue";
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import { Link } from '@inertiajs/vue3';
import NpcAdvanceCard from "./Partials/NpcAdvanceCard.vue";
import InputSwitch from 'primevue/inputswitch';
import {ref, defineProps} from 'vue';

const props = defineProps<{
    baseNpc: BaseNpcResource
    npc: NpcWithDetails
}>()

const npcData = ref(props.npc);

const updateEnabled = async (enabled: boolean) => {
    try {
        await router.patch(route('npcs.toggle-enabled', npcData.value.id));
        // Update local state
        npcData.value.enabled = enabled;
    } catch (error) {
        console.error('Failed to update enabled status:', error);
        // Revert on error
        npcData.value.enabled = !enabled;
    }
}

</script>
<template>
    <AppLayout>

        <ItemHeader
            :route-back="route('npcs.index')"
        >
            <template #header>
                <img v-tooltip="baseNpc.src" :src="baseNpc.src"  alt=""/>
                #{{ npcData.id }} - {{ npcData.name }}
            </template>
        </ItemHeader>

        <Message class="mb-8">
            <div>Przeglądasz konkretnego NPC umieszczonego w konkretnym miescu na mapie. Jeśli widzisz kilka miejsc występowania znaczy, że po zabiciu może odrodzić się w jednej z tych lokalizacji, ale to wciąż ten sam NPC którego można spotkać tylko w jednym miejscu na raz.</div>
        </Message>

        <DetailsCardList title="Informacje Podstawowe" >
            <DetailsCardListItem label="Nazwa" :value="npcData.name"/>
<!--            <DetailsCardListItem label="Link do grafiki" :value="'https://s3.letscode.it/virtigia-assets/img/npc/' + baseNpc.src" />-->
            <DetailsCardListItem label="Lvl" :value="npcData.lvl"/>
<!--            <DetailsCardListItem label="Type" :value="npc.type" />-->
            <DetailsCardListItem label="W Grupie" :value="npcData.in_group"/>
            <DetailsCardListItem label="Enabled">
                <template #value>
                    <InputSwitch v-model="npcData.enabled" @update:model-value="updateEnabled"/>
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Bazowy NPC">
                <template #value>
                    <Link :href="route('base-npcs.show', baseNpc.id)">
                        <Button label="Przejdź" size="small" severity="info" />
                    </Link>
                </template>
            </DetailsCardListItem>
        </DetailsCardList>

        <NpcAdvanceCard :npc="npcData" :baseNpc="baseNpc"/>

        <DetailsCardList title="Miejsce/a wystąpienia" >
            <DetailsCardListItem
                v-for="location in npcData.locations"
                :value="`(${location.x}, ${location.y})`"
            >
                <template #label>
                    <Link :href="route('maps.show', location.map_id)">
                        <Button label="Pokaż mapę" size="small" />
                    </Link>
                    <span class="ml-4 font-bold">{{location.map_name}}</span>
                </template>
            </DetailsCardListItem>
        </DetailsCardList>
    </AppLayout>
</template>

<style scoped>

</style>
