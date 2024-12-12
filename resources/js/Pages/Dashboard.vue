<script setup lang="ts">

import AppLayout from "../layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {MapResource} from "@/Resources/Map.resource";

type Data = {
    data: MapResource
}
</script>

<template>
    <AppLayout>
        <div class="card">
            <AdvanceTable
                prop-name="maps"
            >
                <AdvanceColumn field="id" header="ID" style="width: 5%" />

                <AdvanceColumn field="name" header="Name" style="width: 25%">
                    <template #body="{ data }: Data">
                        <Badge style="background: #31c1d0" class="w-full">
                            <span class="text-lg">
                                {{ data.name }}
                            </span>
                        </Badge>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="activity" header="Rozmiar" style="width: 25%">
                    <template #body="{ data }: Data">
                        ({{data.x}},{{data.y}})
                    </template>
                </AdvanceColumn>

                <Column header="Action" style="width: 20%">
                    <template #body="slotProps">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Button
                                    class="px-2"
                                    icon="pi pi-eye"
                                    label="Podgląd"
                                />
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
