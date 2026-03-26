<script setup lang="ts">

import {route} from "ziggy-js";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {PureNpcWithOnlyLocationsResource} from "@/Resources/Npc.resource";
import {Link} from "@inertiajs/vue3";
import NpcLocationMapPreview from "@/Components/NpcLocationMapPreview.vue";
import Carousel from "primevue/carousel";

defineProps<{
    npcSrc: string
    npcCount: number
}>()

type Data = {
    data: PureNpcWithOnlyLocationsResource
}

const carouselResponsiveOptions = [
    {
        breakpoint: '1400px',
        numVisible: 1,
        numScroll: 1,
    },
    {
        breakpoint: '768px',
        numVisible: 1,
        numScroll: 1,
    },
]

</script>
<template>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-surface-200 bg-surface-50 px-4 py-3 dark:border-surface-700 dark:bg-surface-900">
        <div class="flex flex-col gap-1">
            <span class="text-lg font-semibold text-surface-900 dark:text-surface-0">Powiązane instancje NPC</span>
            <span class="text-sm text-surface-600 dark:text-surface-300">Lista wszystkich konkretnych NPC opartych o ten Base NPC.</span>
        </div>
        <Tag severity="contrast" :value="`${npcCount} NPC`" />
    </div>

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
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <Tag severity="info" :value="`${data.locations.length} lokalizacji`" />
                    </div>

                    <Message v-if="data.locations.length > 1" severity="warn" size="small">
                        Ten NPC ma wiele jednoczesnych lokalizacji i jako heros bedzie respil sie losowo w jednej z nich.
                    </Message>

                    <div v-if="data.locations.length === 1">
                    <NpcLocationMapPreview
                        :location="data.locations[0]"
                        :npc-src="npcSrc"
                        compact
                    />
                    </div>
                    <div v-else-if="data.locations.length > 1" class="max-w-[380px] overflow-hidden">
                        <Carousel
                            class="location-carousel"
                            :value="data.locations"
                            :num-visible="1"
                            :num-scroll="1"
                            :show-navigators="data.locations.length > 1"
                            :show-indicators="data.locations.length > 1"
                            :responsive-options="carouselResponsiveOptions"
                        >
                            <template #item="{ data: location }">
                                <div class="px-2 pb-8">
                                    <NpcLocationMapPreview
                                        :location="location"
                                        :npc-src="npcSrc"
                                        compact
                                    />
                                </div>
                            </template>
                        </Carousel>
                    </div>
                    <div v-else>
                        <Tag
                            value="Wykryto problem! Ten mob nie ma respow!"
                            severity="danger"
                        />
                    </div>
                </div>
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
:deep(.location-carousel) {
    width: 100%;
}

:deep(.location-carousel .p-carousel-content-container) {
    overflow: hidden;
}

:deep(.location-carousel .p-carousel-viewport) {
    overflow: hidden;
}

:deep(.location-carousel .p-carousel-item) {
    min-width: 0;
}
</style>
