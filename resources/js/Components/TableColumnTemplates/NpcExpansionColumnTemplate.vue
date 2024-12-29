<script setup lang="ts">

import {route} from "ziggy-js";
import {NpcResource} from "@/Resources/Npc.resource";
import NpcLocationsColumnTemplate from "@/Components/TableColumnTemplates/NpcLocationsColumnTemplate.vue";
import { Link } from '@inertiajs/vue3';

defineProps<{
    npcs: NpcResource
}>()

</script>
<template>
    <DataTable :value="npcs">

        <Column field="src" header="Grafika">
            <template #body="{data}">
                <img v-tooltip="data.src" :src="'https://virtigia-assets.letscode.it/img/npc/' + data.src" />
            </template>
        </Column>

        <Column field="name" header="Nazwa" />

        <Column field="npc.locations" header="Lokalizacja/e">
            <template #body="{data}">
                <NpcLocationsColumnTemplate :npc="data" />
            </template>
        </Column>


        <Column field="src" header="Akcje">
            <template #body="{data}">
                <Link
                    :href="route('npcs.show', {npc: data.id})"
                >
                    <Button
                        class="px-2"
                        icon="pi pi-eye"
                        label="Podgląd"
                    />
                </Link>
            </template>
        </Column>

    </DataTable>
</template>

<style scoped>

</style>
