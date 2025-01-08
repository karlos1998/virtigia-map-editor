<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";

import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {ref} from "vue";

type Data = {
    data: any
}

const expandedRows = ref();
</script>

<template>
    <AppLayout>


        <div class="card">

            <AdvanceTable
                prop-name="logs"
                v-model:expandedRows="expandedRows"
            >

                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Logi zmian użytkownikoów</h4>
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

                <Column expander style="width: 5rem" />

                <AdvanceColumn field="causer_name" header="Causer"  />

                <AdvanceColumn field="subject_type" header="Subject"  />

                <AdvanceColumn field="log_name" header="Log Name"  />

                <AdvanceColumn field="description" header="Description"  />

                <AdvanceColumn field="event" header="Event"  />

                <template #expansion="{data}: Data">
                    <pre>{{data}}</pre>
                </template>

            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
