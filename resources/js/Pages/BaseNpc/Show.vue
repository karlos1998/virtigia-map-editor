<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcLocationResource} from "@/Resources/NpcLocation.resource";
import BaseNpcLocationsTable from "@/Pages/BaseNpc/Partials/BaseNpcLocationsTable.vue";

defineProps<{
    baseNpc: BaseNpcResource
    locations: NpcLocationResource[]
}>()

</script>
<template>
    <AppLayout>

        <div
            class="card"
        >
            <div>
                <img v-tooltip="baseNpc.src" :src="'https://virtigia-assets.letscode.it/img/npc/' + baseNpc.src" />
                <div class="font-bold">{{ baseNpc.name }}</div>
            </div>
        </div>

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
