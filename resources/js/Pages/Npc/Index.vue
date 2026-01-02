<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {NpcResource, NpcWithLocationsResource} from "@/Resources/Npc.resource";
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import NpcLocationsColumnTemplate from "@/Components/TableColumnTemplates/NpcLocationsColumnTemplate.vue";
import {ref, onMounted} from "vue";
import {router} from "@inertiajs/vue3";
import {MapResource} from "@/Resources/Map.resource";
import AutoComplete from 'primevue/autocomplete';
import Column from 'primevue/column';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Fieldset from 'primevue/fieldset';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';

type Data = {
    data: NpcWithLocationsResource
}

// Reactive state for map search
const selectedMap = ref<MapResource | null>(null);
const mapSuggestions = ref<MapResource[]>([]);
const isFiltersExpanded = ref(false);

// Search maps function
const searchMaps = async (event: any) => {
    if (event.query.length < 2) {
        mapSuggestions.value = [];
        return;
    }

    try {
        const response = await fetch(route('maps.search') + '?search=' + encodeURIComponent(event.query));
        const data = await response.json();
        mapSuggestions.value = data || [];
    } catch (error) {
        console.error('Error searching maps:', error);
        mapSuggestions.value = [];
    }
};

// Apply filters function
const applyFilters = () => {
    const params = new URLSearchParams(window.location.search);

    if (selectedMap.value) {
        params.set('map_id', selectedMap.value.id.toString());
    } else {
        params.delete('map_id');
    }

    const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
    window.history.replaceState({}, '', newUrl);

    // Reload the page with new filter
    router.reload({
        data: {
            map_id: selectedMap.value?.id || null
        }
    });
};

// Initialize selected map from URL on page load
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const mapId = urlParams.get('map_id');
    if (mapId) {
        // Fetch the map details to display in the autocomplete
        fetch(route('maps.data', mapId))
            .then(response => response.json())
            .then(data => {
                selectedMap.value = data;
            })
            .catch(error => {
                console.error('Error fetching map details:', error);
            });
    }
});

</script>

<template>
    <AppLayout>
        <div class="card">
            <Fieldset legend="Filtry zaawansowane" :toggleable="true" v-model:collapsed="isFiltersExpanded"
                      class="mb-4">
                <div class="flex flex-wrap gap-2 items-center">
                    <AutoComplete
                        v-model="selectedMap"
                        :suggestions="mapSuggestions"
                        @complete="searchMaps($event)"
                        option-label="name"
                        placeholder="Wyszukaj mapę"
                        style="min-width: 300px"
                    />
                    <Button @click="applyFilters" label="Zastosuj" severity="success"/>
                </div>
            </Fieldset>
            <AdvanceTable
                prop-name="npcs"
            >


                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista rozmieszczonych npc</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                :value="globalFilterValue"
                                @update:model-value="globalFilterUpdated"
                                placeholder="Szukaj po nazwie, lvl, rank, category"
                            />
                        </IconField>
                    </div>
                </template>

                <AdvanceColumn field="id" header="ID" style="width: 5%" />

                <AdvanceColumn field="src" header="Grafika">
                    <template #body="{ data }: Data">
                        <img v-tooltip="data.src" :src="data.src" />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="name" header="Name">
                    <template #body="{ data }: Data">
                        {{ data.name }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="enabled" header="Enabled">
                    <template #body="{ data }: Data">
                        <Badge :severity="data.enabled ? 'success' : 'danger'">
                            {{ data.enabled ? 'Yes' : 'No' }}
                        </Badge>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="locations" header="Lokalizacja/a">
                    <template #body="{ data }: Data">
                        <NpcLocationsColumnTemplate :npc="data" />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="dialog" header="Dialog">
                    <template #body="{ data }: Data">
                        <div v-if="data.dialog">
                            <Badge severity="success" class="mr-1">
                                <i class="pi pi-check mr-1"></i>
                                <span>Dialog przypisany</span>
                            </Badge>
                        </div>
                        <div v-else>
                            <Badge severity="warning">
                                <i class="pi pi-times mr-1"></i>
                                <span>Brak dialogu</span>
                            </Badge>
                        </div>
                    </template>
                </AdvanceColumn>

                <Column header="Action">
                    <template #body="{ data }: Data">
                        <Link :href="route('npcs.show', data.id)">
                            <Button
                                class="px-2"
                                icon="pi pi-eye"
                                label="Podgląd"
                            />
                        </Link>
                    </template>
                </Column>
            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
