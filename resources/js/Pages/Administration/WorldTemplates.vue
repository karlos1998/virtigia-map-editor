<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layout/AppLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import WorldTemplateUpdateDialog from './WorldTemplateUpdateDialog.vue';
import { useToast } from 'primevue/usetoast';

type WorldTemplate = {
    id: number;
    name: string;
    slug: string;
    connection_name: string;
    remote_database_server: string;
    database_name: string;
    is_active: boolean;
    is_visible: boolean;
    is_production: boolean;
    base_template_id: number | null;
    base_template_name: string | null;
    latest_update: any;
    database_status: 'ok' | 'error';
    database_status_message: string | null;
};

type RemoteDatabaseServer = {
    value: string;
    label: string;
};

const props = defineProps<{
    templates: WorldTemplate[];
    remoteDatabaseServers: RemoteDatabaseServer[];
    defaultRemoteDatabaseServer: string;
}>();

const page = usePage();
const toast = useToast();
const success = computed(() => (page.props.flash as { success?: string | null } | undefined)?.success ?? null);
const form = useForm({
    name: '',
    remote_database_server: props.defaultRemoteDatabaseServer,
    is_production: false,
    base_template_id: null as number | null,
});
const showCreateDialog = ref(false);
const updateTemplate = ref<WorldTemplate | null>(null);
const showUpdateDialog = ref(false);
const baseTemplates = computed(() => props.templates.filter((template) => !template.is_production && template.is_active));

const openCreateDialog = (): void => {
    form.clearErrors();
    showCreateDialog.value = true;
};

const closeCreateDialog = (): void => {
    if (form.processing) {
        return;
    }

    showCreateDialog.value = false;
    form.reset();
    form.clearErrors();
    form.remote_database_server = props.defaultRemoteDatabaseServer;
};

const submit = (): void => {
    form.post(route('administration.world-templates.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreateDialog(),
    });
};

const serverLabel = (server: string): string => {
    return props.remoteDatabaseServers.find((option) => option.value === server)?.label ?? server;
};

const showDatabaseStatus = (template: WorldTemplate): void => {
    if (!template.database_status_message) {
        return;
    }

    toast.add({
        severity: template.database_status === 'ok' ? 'success' : 'error',
        summary: template.database_status === 'ok' ? 'Baza OK' : 'Błąd bazy',
        detail: template.database_status_message,
        life: 10000,
    });
};
</script>

<template>
    <AppLayout title="Template'y światów">
        <div class="flex flex-col gap-4">
            <Message v-if="success" severity="success" :closable="true">
                {{ success }}
            </Message>

            <Card>
                <template #title>
                    Template'y światów
                </template>
                <template #content>
                    <DataTable
                        :value="templates"
                        dataKey="id"
                        responsiveLayout="scroll"
                        class="text-sm"
                    >
                        <Column field="name" header="Nazwa" sortable />
                        <Column field="slug" header="World" sortable />
                        <Column field="connection_name" header="Connection" sortable />
                        <Column field="remote_database_server" header="Zdalna baza" sortable>
                            <template #body="{ data }">
                                {{ serverLabel(data.remote_database_server) }}
                            </template>
                        </Column>
                        <Column field="database_name" header="Database" sortable />
                        <Column header="Rodzaj / baza">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-2">
                                    <Tag :value="data.is_production ? 'Produkcyjny · tylko odczyt' : 'Edytowalny'" :severity="data.is_production ? 'warn' : 'info'" />
                                    <span v-if="data.is_production" class="text-sm">Baza: {{ data.base_template_name }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }">
                                <div class="flex flex-wrap gap-2">
                                    <Tag :value="data.is_active ? 'Aktywny' : 'Wyłączony'" :severity="data.is_active ? 'success' : 'secondary'" />
                                    <Tag v-if="!data.is_visible" value="Ukryty" severity="warn" />
                                    <Tag
                                        :value="data.database_status === 'ok' ? 'Baza OK' : 'Błąd bazy'"
                                        :severity="data.database_status === 'ok' ? 'success' : 'danger'"
                                        class="cursor-pointer"
                                        @click="showDatabaseStatus(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                        <Column header="Aktualizacja">
                            <template #body="{ data }">
                                <Button
                                    v-if="data.is_production"
                                    label="Aktualizuj"
                                    icon="pi pi-sync"
                                    :disabled="!data.is_active"
                                    @click="updateTemplate = data; showUpdateDialog = true"
                                />
                            </template>
                        </Column>
                    </DataTable>

                    <div class="mt-4 flex justify-end">
                        <Button
                            label="Utwórz nowy template"
                            icon="pi pi-plus"
                            @click="openCreateDialog"
                        />
                    </div>
                </template>
            </Card>
        </div>

        <Dialog
            v-model:visible="showCreateDialog"
            modal
            header="Utwórz nowy template"
            :style="{ width: '32rem', maxWidth: '94vw' }"
            @hide="closeCreateDialog"
        >
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <Message v-if="Object.keys(form.errors).length > 0" severity="error" :closable="false">
                    {{ form.errors.name ?? form.errors.remote_database_server ?? form.errors.base_template_id ?? form.errors.is_production }}
                </Message>

                <div class="flex flex-col gap-2">
                    <label for="world-template-name" class="text-sm font-medium text-surface-700 dark:text-surface-200">Nazwa</label>
                    <InputText
                        id="world-template-name"
                        v-model="form.name"
                        autofocus
                        :invalid="Boolean(form.errors.name)"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="form.is_production" input-id="world-template-production" binary />
                    <label for="world-template-production">Template produkcyjny (tylko odczyt)</label>
                </div>
                <div v-if="form.is_production" class="flex flex-col gap-2">
                    <label for="world-template-base">Bazowy template do aktualizacji</label>
                    <Dropdown
                        id="world-template-base"
                        v-model="form.base_template_id"
                        :options="baseTemplates"
                        option-label="name"
                        option-value="id"
                        placeholder="Wybierz edytowalny template"
                        filter
                        :invalid="Boolean(form.errors.base_template_id)"
                    />
                    <Message severity="info" :closable="false">
                        Produkcja nie będzie dostępna w edytorze. Po utworzeniu przygotuj porównanie i zatwierdź pierwszą aktualizację, aby wypełnić ją danymi template’u bazowego.
                    </Message>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="world-template-server" class="text-sm font-medium text-surface-700 dark:text-surface-200">Zdalna baza</label>
                    <Dropdown
                        id="world-template-server"
                        v-model="form.remote_database_server"
                        :options="remoteDatabaseServers"
                        optionLabel="label"
                        optionValue="value"
                        :invalid="Boolean(form.errors.remote_database_server)"
                    />
                </div>
            </form>

            <template #footer>
                <Button
                    label="Anuluj"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    :disabled="form.processing"
                    @click="closeCreateDialog"
                />
                <Button
                    label="Utwórz"
                    icon="pi pi-check"
                    :loading="form.processing"
                    @click="submit"
                />
            </template>
        </Dialog>
        <WorldTemplateUpdateDialog
            v-if="updateTemplate"
            v-model:visible="showUpdateDialog"
            :template="updateTemplate"
        />
    </AppLayout>
</template>
