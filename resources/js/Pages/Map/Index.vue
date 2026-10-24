<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
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
}

const props = withDefaults(defineProps<{
    filters?: MapFilters
}>(), {
    filters: () => ({
        missing_battleground: false,
        is_city: null,
    }),
});

const cityFilter = ref(props.filters.is_city ?? null);
const cityOptions = [
    { label: 'Wszystkie mapy', value: null },
    { label: 'Wyczerpanie zużywane', value: false },
    { label: 'Bez zużywania wyczerpania (miasta)', value: true },
];
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
const isAdvancedFiltersCollapsed = ref(!showMissingBattlegrounds.value && cityFilter.value === null);
const hasActiveAdvancedFilters = computed(() => showMissingBattlegrounds.value || cityFilter.value !== null);

const reloadWithAdvancedFilters = () => {
    const filters: Record<string, string> = {};

    if (showMissingBattlegrounds.value) {
        filters.missing_battleground = '1';
    }

    if (cityFilter.value !== null) {
        filters.is_city = cityFilter.value ? '1' : '0';
    }
    selectedMaps.value = [];
    router.get(route('maps.index'), filters, {
        only: ['maps', 'filters'],
        preserveState: true,
        replace: true,
    });
}

const clearAdvancedFilters = () => {
    showMissingBattlegrounds.value = false;
    cityFilter.value = null;
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
            <AdvanceTable
                v-model:selection="selectedMaps"
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

                <Column selection-mode="multiple" style="width: 3rem" />
                <Column header="Wyczerpanie"><template #body="{ data }"><Tag :value="data.is_city ? 'Nie spada (miasto)' : 'Zużywane'" :severity="data.is_city ? 'success' : 'warn'" /></template></Column>
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
