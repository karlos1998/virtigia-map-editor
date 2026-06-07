<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {MapResource} from "@/Resources/Map.resource";

import { Link, router } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {computed, ref} from "vue";
import Fieldset from 'primevue/fieldset';
import Checkbox from 'primevue/checkbox';

type Data = {
    data: MapResource
}

type MapFilters = {
    missing_battleground: boolean
}

const props = withDefaults(defineProps<{
    filters?: MapFilters
}>(), {
    filters: () => ({
        missing_battleground: false,
    }),
});

const showMissingBattlegrounds = ref(props.filters.missing_battleground);
const isAdvancedFiltersCollapsed = ref(!showMissingBattlegrounds.value);
const hasActiveAdvancedFilters = computed(() => showMissingBattlegrounds.value);

const reloadWithAdvancedFilters = () => {
    const filters: Record<string, string> = {};

    if (showMissingBattlegrounds.value) {
        filters.missing_battleground = '1';
    }

    router.get(route('maps.index'), filters, {
        only: ['maps', 'filters'],
        preserveState: true,
        replace: true,
    });
}

const clearAdvancedFilters = () => {
    showMissingBattlegrounds.value = false;
    reloadWithAdvancedFilters();
}
</script>

<template>
    <AppLayout>

        <div class="card">
            <Link :href="route('maps.create')">
                <Button label="Dodaj nową mapę" />
            </Link>
        </div>

        <div class="card">
            <Fieldset
                legend="Filtry zaawansowane"
                :toggleable="true"
                v-model:collapsed="isAdvancedFiltersCollapsed"
                class="mb-4"
            >
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-filter text-primary" />
                        <h5 class="m-0">Tła walki</h5>
                        <Tag v-if="hasActiveAdvancedFilters" value="aktywne" severity="success" />
                    </div>

                    <label
                        for="missing-battleground-filter"
                        class="flex cursor-pointer items-center gap-3 rounded border border-surface-200 px-3 py-2 text-sm transition-colors hover:border-primary dark:border-surface-700"
                    >
                        <Checkbox
                            v-model="showMissingBattlegrounds"
                            input-id="missing-battleground-filter"
                            :binary="true"
                        />
                        <span class="font-medium">Pokaż mapy bez nadanego tła walki</span>
                    </label>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            label="Zastosuj"
                            icon="pi pi-search"
                            severity="success"
                            @click="reloadWithAdvancedFilters"
                        />
                        <Button
                            label="Wyczyść"
                            icon="pi pi-times"
                            severity="secondary"
                            outlined
                            :disabled="!hasActiveAdvancedFilters"
                            @click="clearAdvancedFilters"
                        />
                    </div>
                </div>
            </Fieldset>

            <AdvanceTable
                prop-name="maps"
            >

                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista Map</h4>
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

                <Column header="Miniatura" style="width: 80px">
                    <template #body="{ data }: Data">
                        <div class="flex items-center justify-center">
                            <img :src="data.thumbnail_src ?? data.src" :alt="data.name"
                                 style="width:64px; height:48px; object-fit:cover;"/>
                        </div>
                    </template>
                </Column>

                <Column header="Action" style="width: 20%">
                    <template #body="{data}">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Link
                                    :href="route('maps.show', {map: data.id})"
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

                <AdvanceColumn field="name" header="Name" />

                <AdvanceColumn field="activity" header="Rozmiar" >
                    <template #body="{ data }: Data">
                        ({{data.x}}x{{data.y}})
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="src" header="Ścieżka"  />

                <AdvanceColumn field="respawn_point" header="Miejsce odrodzenia">
                    <template #body="{data}">
                        <div v-if="data.respawn_point">
                            {{data.respawn_point.map_name}} ({{data.respawn_point.x}}, {{data.respawn_point.y}})
                        </div>
                        <div v-else>-</div>
                    </template>
                </AdvanceColumn>

            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
