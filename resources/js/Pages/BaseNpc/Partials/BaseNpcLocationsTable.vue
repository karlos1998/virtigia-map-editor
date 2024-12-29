<script setup lang="ts">

import {route} from "ziggy-js";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {PureNpcWithOnlyLocationsResource} from "@/Resources/Npc.resource";

type Data = {
    data: PureNpcWithOnlyLocationsResource
}

</script>
<template>

    <AdvanceTable
        prop-name="locations"
    >


        <!-- TODO Szukanie po lokalizacji z relacji, ale libka musi najpierw to obsluzyc -->
<!--        <template #header="{ globalFilterValue, globalFilterUpdated }">-->

<!--            <div class="flex flex-wrap gap-2 items-center justify-between">-->
<!--                <h4 class="m-0">Lista wystąpień tego moba/npc</h4>-->
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
<!--            </div>-->
<!--        </template>-->

        <AdvanceColumn field="id" header="ID" style="width: 5%" />


        <AdvanceColumn field="name" header="Lokalizacja" style="width: 25%">
            <template #body="{ data }: Data">
                <span class="text-lg" v-if="data.locations.length > 0">
                    <span>{{ data.locations[0].map_name }}
                        ({{data.locations[0].x}},{{data.locations[0].y}})</span>
                    <Tag
                        v-if="data.locations.length > 1"
                        :value="'+ ' + (data.locations.length - 1) + ' dodatkowych'"
                        severity="info"
                        v-tooltip="'Dodatkowe respy tego konkretnego moba. Po jego zabiciu może pojawić się w kilku lokalizacjach.'"
                    />
                </span>
                <Tag
                    v-else
                    value="Wykryto problem! Ten mob nie ma respów!"
                    severity="danger"
                />
            </template>
        </AdvanceColumn>

    </AdvanceTable>
</template>

<style scoped>

</style>
