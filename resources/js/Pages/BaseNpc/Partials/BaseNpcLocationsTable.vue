<script setup lang="ts">

import {route} from "ziggy-js";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {PureNpcWithOnlyLocationsResource} from "@/Resources/Npc.resource";
import {Link} from "@inertiajs/vue3";
import NpcLocationMapPreview from "@/Components/NpcLocationMapPreview.vue";

defineProps<{
    npcSrc: string
}>()

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

        <AdvanceColumn field="id" header="ID instancji NPC" style="width: 10%" />


        <AdvanceColumn field="name" header="Lokalizacja" style="width: 55%">
            <template #body="{ data }: Data">
                <div v-if="data.locations.length > 0" class="flex flex-col gap-3">
                    <NpcLocationMapPreview
                        v-for="location in data.locations"
                        :key="location.id"
                        :location="location"
                        :npc-src="npcSrc"
                        compact
                    />
                </div>
                <Tag
                    v-else
                    value="Wykryto problem! Ten mob nie ma respow!"
                    severity="danger"
                />
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
