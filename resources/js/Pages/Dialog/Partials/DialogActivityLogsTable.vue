<script setup lang="ts">
import AdvanceTable from '@advance-table/Components/AdvanceTable.vue';
import AdvanceColumn from '@advance-table/Components/AdvanceColumn.vue';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import { ref } from 'vue';
import type { AdvanceTableResponse } from '@/karlos3098-LaravelPrimevueTable/Services/tableService';

defineProps<{
    logs: AdvanceTableResponse<any>
}>();

const expandedRows = ref([]);

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleString();
};

const formatSubjectType = (subjectType: string | null): string => {
    switch (subjectType) {
        case 'App\\Models\\DialogNode':
            return 'Węzeł';
        case 'App\\Models\\DialogNodeOption':
            return 'Opcja';
        case 'App\\Models\\DialogEdge':
            return 'Połączenie';
        default:
            return 'Element';
    }
};

const formatNodeType = (type: string | null | undefined): string => {
    switch (type) {
        case 'start':
            return 'Start';
        case 'special':
            return 'Dialog';
        case 'randomizer':
            return 'Losowanie';
        case 'shop':
            return 'Sklep';
        case 'teleportation':
            return 'Teleport';
        default:
            return type || 'Node';
    }
};

const getElementName = (data: any): string => {
    const attributes = data?.properties?.attributes ?? {};
    const old = data?.properties?.old ?? {};

    if (data?.subject_type === 'App\\Models\\DialogNode') {
        return formatNodeType(attributes.type ?? old.type);
    }

    if (data?.subject_type === 'App\\Models\\DialogNodeOption') {
        return attributes.label ?? old.label ?? `#${data.subject_id}`;
    }

    if (data?.subject_type === 'App\\Models\\DialogEdge') {
        return `#${data.subject_id}`;
    }

    return `#${data.subject_id}`;
};

const getEventSeverity = (event: string): string => {
    switch (event) {
        case 'created':
            return 'success';
        case 'updated':
            return 'info';
        case 'deleted':
            return 'danger';
        default:
            return 'secondary';
    }
};

const getEventLabel = (event: string): string => {
    switch (event) {
        case 'created':
            return 'Utworzono';
        case 'updated':
            return 'Zaktualizowano';
        case 'deleted':
            return 'Usunięto';
        default:
            return event;
    }
};

const getReadableDescription = (data: any): string => {
    const elementType = formatSubjectType(data?.subject_type);
    const elementName = getElementName(data);
    const eventLabel = getEventLabel(data?.event);

    if (data?.subject_type === 'App\\Models\\DialogEdge') {
        return `${eventLabel} ${elementType.toLowerCase()} ${elementName}`;
    }

    return `${eventLabel} ${elementType.toLowerCase()}: ${elementName}`;
};

const formatValue = (value: unknown): string => {
    if (value === null) {
        return 'null';
    }

    if (typeof value === 'boolean') {
        return value ? 'true' : 'false';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
};

const getFieldLabel = (field: string): string => {
    switch (field) {
        case 'content':
            return 'Treść';
        case 'label':
            return 'Etykieta';
        case 'type':
            return 'Typ';
        case 'position':
            return 'Pozycja';
        case 'rules':
            return 'Reguły';
        case 'action_data':
            return 'Dane akcji';
        case 'additional_actions':
            return 'Akcje dodatkowe';
        case 'source_option_id':
            return 'Opcja źródłowa';
        case 'target_node_id':
            return 'Node docelowy';
        case 'source_node_id':
            return 'Node źródłowy';
        case 'order':
            return 'Kolejność';
        case 'shop_id':
            return 'Sklep';
        default:
            return field;
    }
};

const formatChanges = (properties: any): Array<{ field: string, oldValue: string, newValue: string }> => {
    if (!properties?.attributes || !properties?.old) {
        return [];
    }

    return Object.keys(properties.attributes)
        .filter((key) => properties.old[key] !== undefined && properties.attributes[key] !== properties.old[key])
        .map((key) => ({
            field: getFieldLabel(key),
            oldValue: formatValue(properties.old[key]),
            newValue: formatValue(properties.attributes[key]),
        }));
};
</script>

<template>
    <div class="card mt-6">
        <AdvanceTable prop-name="logs" v-model:expandedRows="expandedRows">
            <template #header="{ globalFilterValue, globalFilterUpdated }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="m-0 text-xl font-semibold">Historia zmian dialogu</h3>
                        <div class="text-sm text-surface-500 mt-1">
                            Zmiany dotyczące węzłów, opcji i połączeń powiązanych z tym dialogiem.
                        </div>
                    </div>

                    <IconField>
                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>
                        <InputText
                            :value="globalFilterValue"
                            @update:model-value="globalFilterUpdated"
                            placeholder="Szukaj w historii zmian"
                        />
                    </IconField>
                </div>
            </template>

            <template #empty>
                <Message>Brak historii zmian dla tego dialogu.</Message>
            </template>

            <Column expander style="width: 4rem" />

            <AdvanceColumn field="event" header="Zdarzenie" style="width: 12%" sortable>
                <template #body="{ data }">
                    <Tag :value="getEventLabel(data.event)" :severity="getEventSeverity(data.event)" />
                </template>
            </AdvanceColumn>

            <AdvanceColumn field="subject_type" header="Element" style="width: 14%" sortable>
                <template #body="{ data }">
                    <div class="font-medium">{{ formatSubjectType(data.subject_type) }}</div>
                    <div class="text-sm text-surface-500">{{ getElementName(data) }}</div>
                </template>
            </AdvanceColumn>

            <AdvanceColumn header="Opis" style="width: 34%">
                <template #body="{ data }">
                    <div class="font-medium">{{ getReadableDescription(data) }}</div>
                </template>
            </AdvanceColumn>

            <AdvanceColumn field="causer_name" header="Użytkownik" style="width: 16%" sortable>
                <template #body="{ data }">
                    {{ data.causer_name || 'System' }}
                </template>
            </AdvanceColumn>

            <AdvanceColumn field="created_at" header="Data" style="width: 18%" sortable>
                <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                </template>
            </AdvanceColumn>

            <template #expansion="{ data }">
                <div class="p-4">
                    <div class="font-semibold mb-3">Szczegóły zmian</div>

                    <DataTable
                        v-if="formatChanges(data.properties).length > 0"
                        :value="formatChanges(data.properties)"
                        class="p-datatable-sm"
                    >
                        <Column field="field" header="Pole" />
                        <Column field="oldValue" header="Stara wartość" />
                        <Column field="newValue" header="Nowa wartość" />
                    </DataTable>

                    <Message v-else severity="secondary">
                        Ten wpis nie zawiera listy zmienionych pól.
                    </Message>
                </div>
            </template>
        </AdvanceTable>
    </div>
</template>
