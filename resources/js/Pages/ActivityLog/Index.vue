<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";

import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {ref} from "vue";
import Panel from 'primevue/panel';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import SelectButton from 'primevue/selectbutton';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';

type Data = {
    data: any
}

const expandedRows = ref();

// Function to format the subject type to a more readable format
const formatSubjectType = (subjectType) => {
    if (!subjectType) return '';

    // Extract the model name from the full namespace
    const parts = subjectType.split('\\');
    return parts[parts.length - 1];
};

// Function to get the subject name from properties if available
const getSubjectName = (properties) => {
    if (!properties) return '';

    // Check for base_npc in properties
    if (properties.base_npc && properties.base_npc.name) {
        return properties.base_npc.name;
    }

    // Check for base_item in properties
    if (properties.base_item && properties.base_item.name) {
        return properties.base_item.name;
    }

    // Try to find a name property in the attributes
    if (properties.attributes && properties.attributes.name) {
        return properties.attributes.name;
    }

    // If no name found, try to find an id
    if (properties.attributes && properties.attributes.id) {
        return `#${properties.attributes.id}`;
    }

    return '';
};

// Function to get the subject ID from properties if available
const getSubjectId = (data) => {
    if (!data) return null;

    // Direct subject ID from the log entry
    if (data.subject_id) {
        return data.subject_id;
    }

    // Try to find ID in properties
    if (data.properties) {
        if (data.properties.base_npc && data.properties.base_npc.id) {
            return data.properties.base_npc.id;
        }

        if (data.properties.base_item && data.properties.base_item.id) {
            return data.properties.base_item.id;
        }

        if (data.properties.attributes && data.properties.attributes.id) {
            return data.properties.attributes.id;
        }
    }

    return null;
};

// Function to get route for subject
const getSubjectRoute = (data) => {
    if (!data || !data.subject_type) return null;

    const subjectType = formatSubjectType(data.subject_type);
    const subjectId = getSubjectId(data);

    if (!subjectId) return null;

    if (subjectType === 'BaseNpc') {
        return route('base-npcs.show', subjectId);
    } else if (subjectType === 'BaseItem') {
        return route('base-items.show', subjectId);
    } else if (subjectType === 'Npc') {
        return route('npcs.show', subjectId);
    }

    return null;
};

// Function to get a human-readable event description
const getEventDescription = (data) => {
    if (!data) return '';

    // Special handling for loot-related events
    if (data.event === 'attach-base-npc-loots' || data.event === 'attach-to-base-npc-loots') {
        const itemName = data.properties?.base_item?.name || 'przedmiot';
        const npcName = data.properties?.base_npc?.name || 'NPC';
        return `Dodano ${itemName} do łupów ${npcName}`;
    }

    if (data.event === 'detach-base-npc-loots' || data.event === 'detach-from-base-npc-loots') {
        const itemName = data.properties?.base_item?.name || 'przedmiot';
        const npcName = data.properties?.base_npc?.name || 'NPC';
        return `Usunięto ${itemName} z łupów ${npcName}`;
    }

    // Generic event descriptions
    if (data.event === 'created') {
        return `Utworzono ${formatSubjectType(data.subject_type)} ${getSubjectName(data.properties)}`;
    }

    if (data.event === 'updated') {
        return `Zaktualizowano ${formatSubjectType(data.subject_type)} ${getSubjectName(data.properties)}`;
    }

    if (data.event === 'deleted') {
        return `Usunięto ${formatSubjectType(data.subject_type)} ${getSubjectName(data.properties)}`;
    }

    return data.description || data.event;
};

// Function to format changes in a readable way
const formatChanges = (properties) => {
    if (!properties || !properties.attributes || !properties.old) return [];

    const changes = [];

    // Compare old and new attributes to find changes
    for (const key in properties.attributes) {
        if (properties.old[key] !== undefined && properties.attributes[key] !== properties.old[key]) {
            changes.push({
                field: key,
                oldValue: properties.old[key],
                newValue: properties.attributes[key]
            });
        }
    }

    return changes;
};

// Function to format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};
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
                        <div class="flex items-center gap-4">
                            <h4 class="m-0">Logi zmian użytkownikoów</h4>
                        </div>
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

                <Column expander style="width: 3rem" />

                <AdvanceColumn field="id" header="ID" style="width: 5%" sortable />

                <AdvanceColumn field="event" header="Zdarzenie" sortable>
                    <template #body="{ data }">
                        <Tag v-if="data.event === 'created'" severity="success" value="Utworzono" />
                        <Tag v-else-if="data.event === 'updated'" severity="info" value="Zaktualizowano" />
                        <Tag v-else-if="data.event === 'deleted'" severity="danger" value="Usunięto" />
                        <Tag v-else-if="data.event === 'attach-base-npc-loots' || data.event === 'attach-to-base-npc-loots'"
                             severity="success" value="Dodano loot" />
                        <Tag v-else-if="data.event === 'detach-base-npc-loots' || data.event === 'detach-from-base-npc-loots'"
                             severity="danger" value="Usunięto loot" />
                        <Tag v-else :value="data.event" />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn header="Opis" sortable>
                    <template #body="{ data }">
                        <div>{{ getEventDescription(data) }}</div>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn header="Obiekt" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-500 mr-2">{{ formatSubjectType(data.subject_type) }}</span>
                            <Link v-if="getSubjectRoute(data)" :href="getSubjectRoute(data)" class="text-blue-500 hover:underline">
                                {{ getSubjectName(data.properties) || `#${getSubjectId(data)}` }}
                            </Link>
                            <span v-else>{{ getSubjectName(data.properties) || `#${getSubjectId(data)}` }}</span>
                        </div>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="causer_name" header="Użytkownik" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center">
                            <Avatar v-if="data.causer_name" icon="pi pi-user" shape="circle" size="small" class="mr-2" />
                            <span>{{ data.causer_name || 'System' }}</span>
                        </div>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="created_at" header="Data" sortable>
                    <template #body="{ data }">
                        {{ formatDate(data.created_at) }}
                    </template>
                </AdvanceColumn>

                <Column field="world" header="Świat" />

                <template #expansion="{data}: Data">
                    <div class="p-4">
                        <div class="mb-4">
                            <h3 class="text-xl font-semibold mb-2">Szczegóły zmiany</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Typ zdarzenia</p>
                                    <p class="font-medium">
                                        <Tag v-if="data.event === 'created'" severity="success" value="Utworzono" />
                                        <Tag v-else-if="data.event === 'updated'" severity="info" value="Zaktualizowano" />
                                        <Tag v-else-if="data.event === 'deleted'" severity="danger" value="Usunięto" />
                                        <Tag v-else-if="data.event === 'attach-base-npc-loots' || data.event === 'attach-to-base-npc-loots'"
                                             severity="success" value="Dodano loot" />
                                        <Tag v-else-if="data.event === 'detach-base-npc-loots' || data.event === 'detach-from-base-npc-loots'"
                                             severity="danger" value="Usunięto loot" />
                                        <Tag v-else :value="data.event" />
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Użytkownik</p>
                                    <p class="font-medium flex items-center">
                                        <Avatar v-if="data.causer_name" icon="pi pi-user" shape="circle" size="small" class="mr-2" />
                                        {{ data.causer_name || 'System' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Model</p>
                                    <p class="font-medium">{{ formatSubjectType(data.subject_type) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Nazwa obiektu</p>
                                    <p class="font-medium">
                                        <Link v-if="getSubjectRoute(data)" :href="getSubjectRoute(data)" class="text-blue-500 hover:underline">
                                            {{ getSubjectName(data.properties) || `#${getSubjectId(data)}` }}
                                        </Link>
                                        <span v-else>{{ getSubjectName(data.properties) || `#${getSubjectId(data)}` }}</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Data</p>
                                    <p class="font-medium">{{ formatDate(data.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Related models section -->
                        <div v-if="data.properties && (data.properties.base_npc || data.properties.base_item)" class="mt-6">
                            <h4 class="text-lg font-semibold mb-2">Powiązane modele</h4>

                            <!-- Base NPC -->
                            <div v-if="data.properties.base_npc" class="mb-4 p-3 border rounded-lg bg-gray-50">
                                <div class="flex items-center mb-2">
                                    <h5 class="text-md font-semibold">BaseNpc</h5>
                                    <Link
                                        v-if="data.properties.base_npc.id"
                                        :href="route('base-npcs.show', data.properties.base_npc.id)"
                                        class="ml-2 text-blue-500 hover:underline"
                                    >
                                        <Button icon="pi pi-external-link" text size="small" />
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="(value, key) in data.properties.base_npc" :key="key" class="flex">
                                        <span class="text-sm text-gray-500 mr-2">{{ key }}:</span>
                                        <span class="text-sm">
                                            <span v-if="key === 'src' && value">
                                                <img :src="value" class="h-8 w-8 object-contain" :alt="data.properties.base_npc.name || 'NPC'" />
                                            </span>
                                            <span v-else-if="value === null">null</span>
                                            <span v-else-if="typeof value === 'object'">{{ JSON.stringify(value) }}</span>
                                            <span v-else>{{ value }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Base Item -->
                            <div v-if="data.properties.base_item" class="mb-4 p-3 border rounded-lg bg-gray-50">
                                <div class="flex items-center mb-2">
                                    <h5 class="text-md font-semibold">BaseItem</h5>
                                    <Link
                                        v-if="data.properties.base_item.id"
                                        :href="route('base-items.show', data.properties.base_item.id)"
                                        class="ml-2 text-blue-500 hover:underline"
                                    >
                                        <Button icon="pi pi-external-link" text size="small" />
                                    </Link>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="(value, key) in data.properties.base_item" :key="key" class="flex">
                                        <span class="text-sm text-gray-500 mr-2">{{ key }}:</span>
                                        <span class="text-sm">
                                            <span v-if="key === 'src' && value">
                                                <img :src="value" class="h-8 w-8 object-contain" :alt="data.properties.base_item.name || 'Item'" />
                                            </span>
                                            <span v-else-if="value === null">null</span>
                                            <span v-else-if="typeof value === 'object'">{{ JSON.stringify(value) }}</span>
                                            <span v-else>{{ value }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Changes section -->
                        <div v-if="formatChanges(data.properties).length > 0" class="mt-6">
                            <h4 class="text-lg font-semibold mb-2">Zmiany</h4>
                            <DataTable :value="formatChanges(data.properties)" class="p-datatable-sm">
                                <Column field="field" header="Pole"></Column>
                                <Column field="oldValue" header="Stara wartość">
                                    <template #body="{ data }">
                                        <span v-if="data.oldValue === null">null</span>
                                        <span v-else-if="typeof data.oldValue === 'object'">{{ JSON.stringify(data.oldValue) }}</span>
                                        <span v-else>{{ data.oldValue }}</span>
                                    </template>
                                </Column>
                                <Column field="newValue" header="Nowa wartość">
                                    <template #body="{ data }">
                                        <span v-if="data.newValue === null">null</span>
                                        <span v-else-if="typeof data.newValue === 'object'">{{ JSON.stringify(data.newValue) }}</span>
                                        <span v-else>{{ data.newValue }}</span>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- Properties section -->
                        <div v-if="data.properties && data.properties.attributes" class="mt-6">
                            <h4 class="text-lg font-semibold mb-2">Atrybuty</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="(value, key) in data.properties.attributes" :key="key">
                                    <p class="text-sm text-gray-500">{{ key }}</p>
                                    <p class="font-medium">
                                        <span v-if="value === null">null</span>
                                        <span v-else-if="typeof value === 'object'">{{ JSON.stringify(value) }}</span>
                                        <span v-else>{{ value }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Debug button to show raw data -->
                        <div class="mt-6">
                            <Panel header="Debug JSON" toggleable collapsed>
                                <pre>{{ data }}</pre>
                            </Panel>
                        </div>
                    </div>
                </template>

            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
