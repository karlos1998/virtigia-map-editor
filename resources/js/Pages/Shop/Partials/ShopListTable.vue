<script setup lang="ts">

import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {MapResource} from "@/Resources/Map.resource";

import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {DialogResource, DialogWithNpcsResource} from "@/Resources/Dialog.resource";
import {ref} from "vue";
import NpcLocationsColumnTemplate from "@/Components/TableColumnTemplates/NpcLocationsColumnTemplate.vue";
import {ShopResource} from "@/Resources/Shop.resource";
import NpcExpansionColumnTemplate from "@/Components/TableColumnTemplates/NpcExpansionColumnTemplate.vue";


type Data = {
    data: ShopResource
}

const expandedRows = ref();
</script>
<template>
    <AdvanceTable
        prop-name="shops"
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

        <AdvanceColumn field="name" header="Nazwa" />

        <AdvanceColumn field="info" header="Ilość przedmiotów" >
            <template #body="{ data }: Data">
                <Tag v-if="data.items_count > 0" :value="data.items_count" v-tooltip="`W sklepie jest ${data.items_count} przedmiotów `" />
                <Tag v-else severity="warn" value="Brak" />
            </template>
        </AdvanceColumn>

        <AdvanceColumn field="info" header="Połączonych dialogów" >
            <template #body="{ data }: Data">
                <Tag v-if="data.dialogs_count > 0" :value="data.dialogs_count" v-tooltip="`W użyciu przez ${data.dialogs_count} dialogów `" />
                <Tag v-else severity="warn" value="Nie używany" />
            </template>
        </AdvanceColumn>

        <AdvanceColumn field="info" header="Połączonych Npc" >
            <template #body="{ data }: Data">
                <Tag v-if="data.npcs.length > 0" :value="data.npcs.length" v-tooltip="`W użyciu przez ${data.dialogs_count} npc `" />
                <Tag v-else severity="warn" value="Nie używany" />
            </template>
        </AdvanceColumn>

        <Column field="src" header="Akcje">
            <template #body="{data}">
                <Link
                    :href="route('shops.show', data.id)"
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
            <NpcExpansionColumnTemplate :npcs="data.npcs" />
        </template>
    </AdvanceTable>
</template>

<style scoped>

</style>
