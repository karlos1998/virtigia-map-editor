<script setup lang="ts">
import {ref} from "vue";
import Panel from 'primevue/panel';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import SelectButton from 'primevue/selectbutton';

const { logs, baseItemId } = defineProps<{
    logs: any[],
    baseItemId: number
}>();

const expandedRows = ref([]);

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

    // Try to find a name property in the properties
    if (properties.attributes && properties.attributes.name) {
        return properties.attributes.name;
    }

    // If no name found, try to find an id
    if (properties.attributes && properties.attributes.id) {
        return `#${properties.attributes.id}`;
    }

    return '';
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
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">Historia zmian</h3>
        </div>

        <DataTable
            :value="logs"
            v-model:expandedRows="expandedRows"
            dataKey="id"
            class="p-datatable-sm"
            :rowHover="true"
            stripedRows
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
            responsiveLayout="scroll"
            v-if="logs && logs.length > 0"
        >
            <Column expander style="width: 3rem" />
            <Column field="id" header="ID" style="width: 5rem" sortable />
            <Column field="event" header="Zdarzenie" sortable>
                <template #body="{ data }">
                    <Tag v-if="data.event === 'created'" severity="success" value="Utworzono" />
                    <Tag v-else-if="data.event === 'updated'" severity="info" value="Zaktualizowano" />
                    <Tag v-else-if="data.event === 'deleted'" severity="danger" value="Usunięto" />
                    <Tag v-else :value="data.event" />
                </template>
            </Column>
            <Column field="causer_name" header="Użytkownik" sortable />
            <Column field="created_at" header="Data" sortable>
                <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                </template>
            </Column>
            <Column field="description" header="Opis" sortable />

            <template #expansion="{ data }">
                <!-- Nice view -->
                <div class="p-4">
                    <!-- Changes section -->
                    <div v-if="formatChanges(data.properties).length > 0" class="mt-2">
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

                    <!-- Debug button to show raw data -->
                    <div class="mt-4">
                        <Panel header="Debug JSON" toggleable collapsed>
                            <pre>{{ data }}</pre>
                        </Panel>
                    </div>
                </div>
            </template>

            <template #empty>
                <div class="text-center p-4">
                    Brak historii zmian dla tego przedmiotu
                </div>
            </template>
        </DataTable>

        <div v-if="!logs || logs.length === 0" class="text-center p-4">
            Brak historii zmian dla tego przedmiotu
        </div>
    </div>
</template>
