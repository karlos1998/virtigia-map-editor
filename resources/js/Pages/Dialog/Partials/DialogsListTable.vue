<script setup lang="ts">

import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";

import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {DialogResource, DialogWithNpcsResource} from "@/Resources/Dialog.resource";
import {ref} from "vue";
import NpcExpansionColumnTemplate from "@/Components/TableColumnTemplates/NpcExpansionColumnTemplate.vue";


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

        <template #header="{ globalFilterValue, globalFilterUpdated }">

            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div class="font-extrabold">Lista Dialogów</div>
<!--                <IconField>-->
<!--                    <InputIcon>-->
<!--                        <i class="pi pi-search" />-->
<!--                    </InputIcon>-->
<!--                    <InputText-->
<!--                        :value="globalFilterValue"-->
<!--                        @update:model-value="globalFilterUpdated"-->
<!--                        placeholder="Szukaj"-->
<!--                    />-->
<!--                </IconField>-->
            </div>
        </template>

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
            <NpcExpansionColumnTemplate :npcs="data.npcs" />
        </template>
    </AdvanceTable>
</template>

<style scoped>

</style>
