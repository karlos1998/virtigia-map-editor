<script setup lang="ts">

import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {MapResource} from "@/Resources/Map.resource";

import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {DialogResource, DialogWithNpcsResource} from "@/Resources/Dialog.resource";
import {ref} from "vue";
import NpcLocationsColumnTemplate from "@/Components/TableColumnTemplates/NpcLocationsColumnTemplate.vue";


type Data = {
    data: DialogWithNpcsResource
}

const expandedRows = ref();
</script>
<template>
    <AdvanceTable
        prop-name="dialogs"
        v-model:expandedRows="expandedRows"
    >

        <!--                <template #header="{ globalFilterValue, globalFilterUpdated }">-->

        <!--                    <div class="flex flex-wrap gap-2 items-center justify-between">-->
        <!--                        <h4 class="m-0">Lista Map</h4>-->
        <!--                        <IconField>-->
        <!--                            <InputIcon>-->
        <!--                                <i class="pi pi-search" />-->
        <!--                            </InputIcon>-->
        <!--                            <InputText-->
        <!--                                :value="globalFilterValue"-->
        <!--                                @update:model-value="globalFilterUpdated"-->
        <!--                                placeholder="Szukaj"-->
        <!--                            />-->
        <!--                        </IconField>-->
        <!--                    </div>-->
        <!--                </template>-->


        <AdvanceColumn field="id" header="ID" style="width: 5%" />

        <Column expander style="width: 5rem" />


        <!--                <AdvanceColumn field="name" header="Name" style="width: 25%">-->
        <!--                    <template #body="{ data }: Data">-->
        <!--                        {{ data.name }}-->
        <!--                    </template>-->
        <!--                </AdvanceColumn>-->

        <AdvanceColumn field="info" header="Informacje" >
            <template #body="{ data }: Data">
                <Tag v-if="data.npcs_count > 0" :value="`W użyciu przez ${data.npcs_count} npc `" />
                <Tag v-else severity="warn" value="Ten dialog nie jest używany" />
            </template>
        </AdvanceColumn>


        <Column header="Action">
            <template #body="{data}">
                <Link
                    :href="route('dialogs.show', data.id)"
                >
                    <Button
                        class="px-2"
                        icon="pi pi-eye"
                        label="Podgląd"
                    />
                </Link>
            </template>
        </Column>


        <template #expansion="{data}: Data">
            <DataTable :value="data.npcs">

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


                <Column field="src" header="Grafika">
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
    </AdvanceTable>
</template>

<style scoped>

</style>
