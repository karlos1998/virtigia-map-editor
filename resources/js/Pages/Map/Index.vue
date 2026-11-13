<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import type { AdvanceTableResponse } from "@advance-table/Services/tableService";
import {MapResource} from "@/Resources/Map.resource";

import { Link, router, useForm } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {computed, ref} from "vue";
import Fieldset from 'primevue/fieldset';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';

type Data = {
    data: MapResource
}

type MapFilters = {
    is_city: boolean | null
    missing_battleground: boolean
    map_group: string | null
}

const props = withDefaults(defineProps<{
    maps: AdvanceTableResponse<MapResource>
    filters?: MapFilters
    mapGroupList?: { value: string; label: string }[]
}>(), {
    filters: () => ({
        missing_battleground: false,
        is_city: null,
        map_group: null,
    }),
    mapGroupList: () => [],
});

const searchDraft = ref(props.maps.tableData.globalFilter ?? '');
const cityFilter = ref(props.filters.is_city ?? null);
const cityOptions = [
    { label: 'Wszystkie mapy', value: null },
    { label: 'Wyczerpanie zużywane', value: false },
    { label: 'Bez zużywania wyczerpania (miasta)', value: true },
];
const mapGroupFilter = ref<string | null>(props.filters.map_group ?? null);
const mapGroupOptions = computed(() => [
    { label: 'Wszystkie grupy', value: null },
    ...props.mapGroupList,
]);
const mapGroupLabels = computed(() => Object.fromEntries(props.mapGroupList.map(option => [option.value, option.label])));
const selectedMaps = ref<MapResource[]>([]);
const bulkVisible = ref(false);
const bulkForm = useForm({ map_ids: [] as number[], is_city: true });
const openBulk = (isCity: boolean) => {
    bulkForm.clearErrors();
    bulkForm.map_ids = selectedMaps.value.map(map => map.id);
    bulkForm.is_city = isCity;
    bulkVisible.value = true;
};
const saveBulk = () => bulkForm.patch(route('maps.bulk.city'), {
    preserveScroll: true,
    onSuccess: () => { bulkVisible.value = false; selectedMaps.value = []; },
});
const showMissingBattlegrounds = ref(props.filters.missing_battleground);
const isAdvancedFiltersCollapsed = ref(!showMissingBattlegrounds.value && cityFilter.value === null && mapGroupFilter.value === null);
const hasActiveAdvancedFilters = computed(() => showMissingBattlegrounds.value || cityFilter.value !== null || mapGroupFilter.value !== null);

const reloadMaps = (advancedFilters: MapFilters, search: string) => {
    const filters: Record<string, string> = {};

    if (advancedFilters.missing_battleground) {
        filters.missing_battleground = '1';
    }

    if (advancedFilters.is_city !== null) {
        filters.is_city = advancedFilters.is_city ? '1' : '0';
    }

    if (advancedFilters.map_group !== null) {
        filters.map_group = advancedFilters.map_group;
    }

    const { tableData, meta } = props.maps;
    selectedMaps.value = [];
    router.get(route('maps.index'), {
        ...filters,
        tables: {
            [tableData.propName]: {
                globalFilter: search,
                page: 1,
                perPage: meta.per_page,
                filters: JSON.stringify(tableData.activeFilters),
                sortOrder: tableData.sortOrder,
                sortField: tableData.sortField,
            },
        },
    }, {
        only: ['maps', 'filters'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const searchMaps = () => reloadMaps(props.filters, searchDraft.value.trim());

const reloadWithAdvancedFilters = () => reloadMaps({
    missing_battleground: showMissingBattlegrounds.value,
    is_city: cityFilter.value,
    map_group: mapGroupFilter.value,
}, props.maps.tableData.globalFilter ?? '');

const clearAdvancedFilters = () => {
    showMissingBattlegrounds.value = false;
    cityFilter.value = null;
    mapGroupFilter.value = null;
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
                        <h5 class="m-0">Ustawienia map</h5>
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

                    <label class="flex flex-col gap-2">
                        <span>Zużywanie wyczerpania</span>
                        <Select v-model="cityFilter" :options="cityOptions" option-label="label" option-value="value" />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span>Grupa mapy</span>
                        <Select v-model="mapGroupFilter" :options="mapGroupOptions" option-label="label" option-value="value" />
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

            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span>Zaznaczono: {{ selectedMaps.length }}</span>
                <Button label="Wyłącz wyczerpanie" :disabled="!selectedMaps.length" @click="openBulk(true)" />
                <Button label="Włącz wyczerpanie" severity="secondary" :disabled="!selectedMaps.length" @click="openBulk(false)" />
            </div>
            <Dialog v-model:visible="bulkVisible" modal header="Masowa zmiana wyczerpania" :closable="!bulkForm.processing" :close-on-escape="!bulkForm.processing">
                <p>{{ bulkForm.is_city ? 'Wyłączyć' : 'Włączyć' }} zużywanie wyczerpania na {{ bulkForm.map_ids.length }} zaznaczonych mapach?</p>
                <p class="my-3">Zmiana dotyczy wyłącznie zaznaczonych map.</p>
                <Message v-for="(message, field) in bulkForm.errors" :key="field" severity="error">{{ message }}</Message>
                <template #footer>
                    <Button label="Anuluj" severity="secondary" :disabled="bulkForm.processing" @click="bulkVisible = false" />
                    <Button label="Potwierdź" :loading="bulkForm.processing" @click="saveBulk" />
                </template>
            </Dialog>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <h4 class="m-0">Lista Map</h4>
                <form class="flex flex-wrap items-center gap-2" role="search" @submit.prevent="searchMaps">
                    <InputText v-model="searchDraft" aria-label="Szukaj map" placeholder="Nazwa lub grafika" />
                    <Button type="submit" label="Szukaj" icon="pi pi-search" />
                </form>
            </div>

            <AdvanceTable
                v-model:selection="selectedMaps"
                prop-name="maps"
            >
                <Column selection-mode="multiple" style="width: 3rem" />
                <Column header="Wyczerpanie"><template #body="{ data }"><Tag :value="data.is_city ? 'Nie spada (miasto)' : 'Zużywane'" :severity="data.is_city ? 'success' : 'warn'" /></template></Column>
                <AdvanceColumn field="id" header="ID" style="width: 5%" />
                <Column header="Grupa"><template #body="{ data }: Data"><Tag :value="mapGroupLabels[data.map_group ?? 'no_group'] ?? data.map_group" severity="secondary" /></template></Column>

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

                <AdvanceColumn field="path" header="Nazwa grafiki">
                    <template #body="{ data }: Data">
                        <span class="text-sm text-surface-500 dark:text-surface-400 break-all" :title="data.path">
                            {{ data.path?.split('/').pop() || '—' }}
                        </span>
                    </template>
                </AdvanceColumn>

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
