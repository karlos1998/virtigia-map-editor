<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {NpcResource, NpcWithLocationsResource} from "@/Resources/Npc.resource";
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import NpcLocationsColumnTemplate from "@/Components/TableColumnTemplates/NpcLocationsColumnTemplate.vue";
import {ref, watch} from "vue";
import NpcAdvancedFilters, {type NpcFilters} from '@/Components/NpcAdvancedFilters.vue';
import BulkUpdateNpcsDialog from './Components/BulkUpdateNpcsDialog.vue';
import Menu from 'primevue/menu';
import Column from 'primevue/column';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';

type Data = {
    data: NpcWithLocationsResource
}

const props = defineProps<{
    filters: NpcFilters;
    selectedMap: { id: number; name: string } | null;
    selectedDialog: { id: number; name: string } | null;
    selectedBaseNpc: { id: number; name: string } | null;
    npcs: unknown;
}>();
const selectedNpcs = ref<NpcResource[]>([]);
const bulkMenu = ref();
const bulkVisible = ref(false);
const bulkOperation = ref('');
const bulkActions = [
    { label: 'Przypisz / zamień dialog', operation: 'assign_dialog', icon: 'pi pi-comments' },
    { label: 'Odepnij dialogi', operation: 'detach_dialog', icon: 'pi pi-link' },
    { label: 'Włącz instancje', operation: 'enable', icon: 'pi pi-check' },
    { label: 'Wyłącz instancje', operation: 'disable', icon: 'pi pi-ban' },
    { label: 'Włącz automatyczny dialog', operation: 'enable_auto_dialog', icon: 'pi pi-play' },
    { label: 'Wyłącz automatyczny dialog', operation: 'disable_auto_dialog', icon: 'pi pi-pause' },
    { label: 'Włącz agresywność bazowych mobów', operation: 'enable_aggression', icon: 'pi pi-bolt' },
    { label: 'Wyłącz agresywność bazowych mobów', operation: 'disable_aggression', icon: 'pi pi-shield' },
].map(action => ({ ...action, command: () => { bulkOperation.value = action.operation; bulkVisible.value = true; } }));
watch(() => props.npcs, () => { selectedNpcs.value = []; });

</script>

<template>
    <AppLayout>
        <div class="card">
            <NpcAdvancedFilters :filters="filters" :selected-map="selectedMap" :selected-dialog="selectedDialog" :selected-base-npc="selectedBaseNpc" />
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <span>Zaznaczono: {{ selectedNpcs.length }} / 500</span>
                <Button label="Akcje grupowe" icon="pi pi-list" :disabled="selectedNpcs.length === 0 || selectedNpcs.length > 500" @click="bulkMenu.toggle($event)" />
                <Button v-if="selectedNpcs.length" label="Wyczyść zaznaczenie" severity="secondary" text @click="selectedNpcs = []" />
                <Menu ref="bulkMenu" :model="bulkActions" popup />
            </div>
            <AdvanceTable
                prop-name="npcs"
                v-model:selection="selectedNpcs"
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
                                @update:model-value="value => { selectedNpcs = []; globalFilterUpdated(value); }"
                                placeholder="Szukaj po nazwie, lvl, rank, category"
                            />
                        </IconField>
                    </div>
                </template>

                <Column selection-mode="multiple" style="width: 3rem" />
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

                <Column field="category" header="Kategoria" />
                <Column field="lvl" header="Poziom" />
                <Column field="rank" header="Ranga" />
                <Column header="Agresywny">
                    <template #body="{ data }"><Badge :severity="data.is_aggressive ? 'danger' : 'secondary'">{{ data.is_aggressive ? 'Tak' : 'Nie' }}</Badge></template>
                </Column>
                <Column header="Automatyczny dialog">
                    <template #body="{ data }"><Badge :severity="data.auto_start_dialog ? 'success' : 'secondary'">{{ data.auto_start_dialog ? 'Tak' : 'Nie' }}</Badge></template>
                </Column>

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
                                <Link :href="route('dialogs.show', data.dialog.id)">{{ data.dialog.name }} (#{{ data.dialog.id }})</Link>
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
            <BulkUpdateNpcsDialog v-model:visible="bulkVisible" :npc-ids="selectedNpcs.map(npc => npc.id)" :operation="bulkOperation" @completed="selectedNpcs = []" />
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
