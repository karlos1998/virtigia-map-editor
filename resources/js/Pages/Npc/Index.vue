<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {NpcResource, NpcWithLocationsResource} from "@/Resources/Npc.resource";
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import NpcLocationsColumnTemplate from "@/Components/TableColumnTemplates/NpcLocationsColumnTemplate.vue";

type Data = {
    data: NpcWithLocationsResource
}
</script>

<template>
    <AppLayout>
        <div class="card">
            <AdvanceTable
                prop-name="npcs"
            >


<!--                <template #header="{ globalFilterValue, globalFilterUpdated }">-->

<!--                    <div class="flex flex-wrap gap-2 items-center justify-between">-->
<!--                        <h4 class="m-0">Lista rozmieszczonych npc</h4>-->
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

                <AdvanceColumn field="src" header="Grafika">
                    <template #body="{ data }: Data">
                        <img v-tooltip="data.src" :src="data.src" />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="name" header="Name">
                    <template #body="{ data }: Data">
                        {{ data.name }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="locations" header="Lokalizacja/a">
                    <template #body="{ data }: Data">
                        <NpcLocationsColumnTemplate :npc="data" />
                    </template>
                </AdvanceColumn>

                <Column header="Action">
                    <template #body="{ data }: Data">
                        <Link :href="route('npcs.show', data.id)">
                            <Button
                                class="px-2"
                                icon="pi pi-eye"
                                label="Podgląd"
                            />
                        </Link>
                    </template>
                </Column>
            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
