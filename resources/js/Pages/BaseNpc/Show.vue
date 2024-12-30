<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcLocationResource} from "@/Resources/NpcLocation.resource";
import BaseNpcLocationsTable from "@/Pages/BaseNpc/Partials/BaseNpcLocationsTable.vue";
import {route} from "ziggy-js";
import ItemHeader from "@/Components/ItemHeader.vue";

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

        <div
            class="card"
        >
            Tutaj pojawią się dane i statystyki bazowe.
        </div>

        <div class="card">
            <BaseNpcLocationsTable />
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
