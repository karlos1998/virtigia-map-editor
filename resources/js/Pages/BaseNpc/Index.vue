<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";

type Data = {
    data: BaseNpcResource
}
</script>

<template>
    <AppLayout>

        <div class="card">
            <Link :href="route('base-npcs.create')">
                <Button label="Dodaj bazowego NPC" />
            </Link>
        </div>

        <div class="card">

            <AdvanceTable
                prop-name="baseNpcs"
            >

                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista Bazowych Npc</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                :value="globalFilterValue"
                                @update:model-value="globalFilterUpdated"
                                placeholder="Szukaj"
                            />
                        </IconField>
                    </div>
                </template>

                <AdvanceColumn field="id" header="ID" style="width: 5%" />

                <AdvanceColumn field="src" header="Grafika" style="width: 25%">
                    <template #body="{ data }: Data">

                        <img
                            v-tip.npc="data"
                            :src="'https://s3.letscode.it/virtigia-assets/img/npc/' + data.src"
                        />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="name" header="Name" style="width: 25%">
                    <template #body="{ data }: Data">
                        {{ data.name }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="lvl" header="Lvl">
                    <template #body="{ data }: Data">
                        <Badge style="background: #31c1d0" class="w-full">
                            {{ data.lvl }}
                        </Badge>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="location_count" header="Ilość Wystąpień">
                    <template #body="{ data }: Data">
                        <Badge style="background: #31c1d0" class="w-full">
                            {{ data.location_count }}
                        </Badge>
                    </template>
                </AdvanceColumn>

                <Column header="Action" style="width: 20%">
                    <template #body="{data}">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Link
                                    :href="route('base-npcs.show', {baseNpc: data.id})"
                                >
                                    <Button
                                        class="px-2"
                                        icon="pi pi-eye"
                                        label="Podgląd"
                                    />
                                </Link>


                                <!-- @click="navigateToApiKeysShow(slotProps.data.id)" -->
                                <!--                                        <Button class="px-5" label="Edytuj" icon="pi pi-pencil"/>-->
                                <!--                                        <Button class="px-2" severity="danger" icon="pi pi-trash"/>-->
                            </span>
                        </div>
                    </template>
                </Column>
            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
