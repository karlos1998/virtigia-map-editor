<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {BaseItemResource} from "@/Resources/BaseItem.resource";

type Data = {
    data: BaseItemResource
}
</script>

<template>
    <AppLayout>
        <div class="card">
            <AdvanceTable
                prop-name="items"
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

                <Column header="Action" style="width: 20%">
                    <template #body="slotProps">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Button
                                    class="px-2"
                                    icon="pi pi-eye"
                                    label="Podgląd"
                                />
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
