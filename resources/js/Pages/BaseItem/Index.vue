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

                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista Bazowych Przedmiotów</h4>
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

                <AdvanceColumn field="src" header="Grafika">
                    <template #body="{ data }: Data">
                        <img alt="" :src="`https://s3.letscode.it/virtigia-assets/img/${data.src}`" />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="name" header="Name" />

                <Column header="Action" >
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
