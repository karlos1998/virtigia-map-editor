<script setup lang="ts">

import {route} from "ziggy-js";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {PureNpcWithOnlyLocationsResource} from "@/Resources/Npc.resource";
import {Link} from "@inertiajs/vue3";
import NpcLocationsColumnTemplate from "@/Components/TableColumnTemplates/NpcLocationsColumnTemplate.vue";

type Data = {
    data: PureNpcWithOnlyLocationsResource
}

</script>
<template>

    <AdvanceTable
        prop-name="locations"
    >

        <template #empty>
            <Message>Ten NPC nie występuje jeszcze na żadnej mapie</Message>
        </template>

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
                <NpcLocationsColumnTemplate :npc="data" />
            </template>
        </AdvanceColumn>

        <Column header="Action" style="width: 20%">
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

    </AdvanceTable>
</template>

<style scoped>

</style>
