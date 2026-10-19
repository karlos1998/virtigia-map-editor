<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Message from 'primevue/message';
import ProgressBar from 'primevue/progressbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';

type Counts = { added: number; updated: number; deleted: number; unchanged: number };
type MigrationStep = {
    template_id: number; template_name: string; role: 'source' | 'target'; name: string;
    status: 'pending' | 'running' | 'completed' | 'failed'; error: string | null;
};
type Status = {
    id: number; target_template_id: number; status: string; progress: number; message: string;
    summary: Record<string, Counts> | null; expires_at: string | null; snapshot_pruned: boolean;
    table_labels?: Record<string, string>;
    migration_steps?: MigrationStep[] | null;
};
type Change = { id: number; table_name: string; row_label: string; change_type: string };
type Detail = { before: Record<string, unknown> | null; after: Record<string, unknown> | null; changed_columns: string[] };
const props = defineProps<{ template: { id: number; name: string; base_template_name: string | null; latest_update: Status | null } }>();
const visible = defineModel<boolean>('visible', { default: false });
const status = ref<Status | null>(null);
const busy = ref(false);
const error = ref('');
const confirmApply = ref(false);
const confirmMigrations = ref(false);
const changes = ref<Change[]>([]);
const changesLoading = ref(false);
const total = ref(0);
const currentPage = ref(0);
const table = ref<string | null>(null);
const type = ref<string | null>(null);
const search = ref('');
const detail = ref<Detail | null>(null);
const detailVisible = ref(false);
const detailLoading = ref(false);
const selectedChange = ref<Change | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let session = 0;
let listRequest = 0;
let detailRequest = 0;
const knownStatuses = new Map<number, Status>();
const running = computed(() => ['queued', 'comparing', 'queued_apply', 'applying', 'verification_required', 'queued_migration', 'migrating'].includes(status.value?.status ?? ''));
const migrationGroups = computed(() => (['source', 'target'] as const).map((role) => {
    const steps = (status.value?.migration_steps ?? []).filter((step) => step.role === role);
    return { role, name: steps[0]?.template_name, steps, completed: steps.filter((step) => step.status === 'completed').length };
}).filter((group) => group.steps.length > 0));
const migrationLabel = (step: MigrationStep): string => step.name === '__repository' ? 'Przygotowanie rejestru migracji' : step.name;
const migrationState = (value: MigrationStep['status']): string => ({ pending: 'Oczekuje', running: 'W trakcie', completed: 'Wykonana', failed: 'Błąd' })[value];
const tableLabel = (name: string): string => status.value?.table_labels?.[name] ?? name;
const summary = computed(() => Object.entries(status.value?.summary ?? {}).map(([name, counts]) => ({ name, label: tableLabel(name), ...counts })));
const changedCount = computed(() => summary.value.reduce((sum, row) => sum + row.added + row.updated + row.deleted, 0));
const canApply = computed(() => status.value?.status === 'ready' && changedCount.value > 0 && !status.value.snapshot_pruned && !!status.value.expires_at && new Date(status.value.expires_at).getTime() > Date.now());
const types = [{ label: 'Dodane', value: 'added' }, { label: 'Zmienione', value: 'updated' }, { label: 'Usunięte', value: 'deleted' }];
const typeLabel = (value: string): string => types.find((item) => item.value === value)?.label ?? value;
const severity = (value: string): 'success' | 'info' | 'danger' => value === 'added' ? 'success' : value === 'deleted' ? 'danger' : 'info';
const errorMessage = (failure: any): string => Object.values(failure.response?.data?.errors ?? {}).flat().join(' ') || failure.response?.data?.message || 'Nie udało się połączyć z serwerem. Spróbuj ponownie.';
const format = (value: unknown): string => value === undefined || value === null ? '—' : typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value);

const loadChanges = async (): Promise<void> => {
    if (!status.value || status.value.snapshot_pruned) return;
    const request = ++listRequest;
    const generation = session;
    changesLoading.value = true;
    try {
        const response = await axios.get(route('administration.world-template-updates.changes', status.value.id), { params: { table: table.value, type: type.value, search: search.value || null, page: currentPage.value + 1 } });
        if (request !== listRequest || generation !== session) return;
        changes.value = response.data.data;
        total.value = response.data.total;
    } catch (failure) {
        if (request === listRequest && generation === session) error.value = errorMessage(failure);
    } finally {
        if (request === listRequest && generation === session) changesLoading.value = false;
    }
};

const poll = async (generation: number): Promise<void> => {
    if (!visible.value || !status.value || generation !== session) return;
    try {
        const response = await axios.get(route('administration.world-template-updates.show', status.value.id));
        if (generation !== session) return;
        const previous = status.value.status;
        status.value = response.data;
        knownStatuses.set(props.template.id, response.data);
        error.value = '';
        if (status.value?.status === 'ready' && previous !== 'ready') await loadChanges();
        if (status.value?.status === 'completed' && previous !== 'completed') await loadChanges();
    } catch (failure) {
        if (generation === session) error.value = errorMessage(failure);
    }
    if (visible.value && generation === session && running.value) timer = setTimeout(() => poll(generation), ['queued_migration', 'migrating'].includes(status.value?.status ?? '') ? 1000 : 2000);
};

const start = async (): Promise<void> => {
    const generation = session;
    busy.value = true;
    error.value = '';
    currentPage.value = 0;
    try {
        const response = await axios.post(route('administration.world-template-updates.store', props.template.id), { confirmed: true });
        if (generation !== session) return;
        status.value = response.data;
        knownStatuses.set(props.template.id, response.data);
        clearTimeout(timer);
        await poll(generation);
    } catch (failure) {
        if (generation === session) error.value = errorMessage(failure);
    } finally {
        if (generation === session) busy.value = false;
    }
};

const apply = async (): Promise<void> => {
    if (!status.value) return;
    const generation = session;
    busy.value = true;
    error.value = '';
    try {
        const response = await axios.post(route('administration.world-template-updates.approve', status.value.id), { confirmed: true });
        if (generation !== session) return;
        status.value = response.data;
        knownStatuses.set(props.template.id, response.data);
        confirmApply.value = false;
        await poll(generation);
    } catch (failure) {
        if (generation === session) error.value = errorMessage(failure);
    } finally {
        if (generation === session) busy.value = false;
    }
};

const migrate = async (): Promise<void> => {
    const generation = session;
    busy.value = true;
    error.value = '';
    try {
        const response = await axios.post(route('administration.world-template-updates.migrate', props.template.id), { confirmed: true });
        if (generation !== session) return;
        status.value = response.data;
        knownStatuses.set(props.template.id, response.data);
        confirmMigrations.value = false;
        confirmApply.value = false;
        currentPage.value = 0;
        clearTimeout(timer);
        await poll(generation);
    } catch (failure) {
        if (generation === session) error.value = errorMessage(failure);
    } finally {
        if (generation === session) busy.value = false;
    }
};

const showDetail = async (change: Change): Promise<void> => {
    if (!status.value) return;
    const request = ++detailRequest;
    selectedChange.value = change;
    detail.value = null;
    detailVisible.value = true;
    detailLoading.value = true;
    try {
        const response = await axios.get(route('administration.world-template-updates.detail', { update: status.value.id, row: change.id }));
        if (request === detailRequest) detail.value = response.data;
    } catch (failure) {
        if (request === detailRequest) error.value = errorMessage(failure);
    } finally {
        if (request === detailRequest) detailLoading.value = false;
    }
};

const resetPage = (): void => { currentPage.value = 0; loadChanges(); };
watch([table, type], resetPage);
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(resetPage, 350); });
watch(visible, async (open) => {
    session++;
    clearTimeout(timer);
    clearTimeout(searchTimer);
    confirmApply.value = false;
    confirmMigrations.value = false;
    detailVisible.value = false;
    detailRequest++;
    busy.value = false;
    error.value = '';
    if (!open) return;
    status.value = knownStatuses.get(props.template.id) ?? props.template.latest_update;
    changes.value = [];
    total.value = 0;
    table.value = null;
    type.value = null;
    search.value = '';
    currentPage.value = 0;
    if (status.value) {
        await poll(session);
        if (['ready', 'completed'].includes(status.value?.status ?? '')) await loadChanges();
    }
}, { immediate: true });
onBeforeUnmount(() => { session++; detailRequest++; clearTimeout(timer); clearTimeout(searchTimer); });
</script>

<template>
    <div>
    <Dialog v-model:visible="visible" modal :header="`Aktualizacja: ${template.name}`" :style="{ width: '76rem', maxWidth: '96vw' }" :closable="!busy" :close-on-escape="!busy">
        <div class="flex flex-col gap-4">
            <p>Bazowy template: <strong>{{ template.base_template_name }}</strong></p>
            <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
            <template v-if="!status">
                <Message severity="warn" :closable="false">Rozpocząć proces aktualizacji? Najpierw przygotujemy porównanie danych. Produkcja zostanie zmieniona dopiero po przeglądzie i osobnym zatwierdzeniu.</Message>
                <Button label="Tak, przygotuj porównanie" icon="pi pi-search" :loading="busy" @click="start" />
            </template>
            <template v-else>
                <Message :severity="status.status === 'failed' ? 'error' : status.status === 'completed' ? 'success' : 'info'" :closable="false">{{ status.message }}</Message>
                <ProgressBar v-if="running" :value="status.progress" :mode="status.progress === 0 ? 'indeterminate' : 'determinate'" />
                <p v-if="running" class="text-sm text-surface-500">Operacja działa w tle. Możesz zamknąć okno i wrócić przez „Aktualizuj”.</p>
                <section v-if="migrationGroups.length" class="flex flex-col gap-3" aria-live="polite" aria-label="Postęp migracji">
                    <h3 class="m-0 text-lg font-semibold">Migracje baz</h3>
                    <div v-for="group in migrationGroups" :key="group.role" class="rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <strong>{{ group.role === 'source' ? 'Template bazowy' : 'Produkcja' }}: {{ group.name }}</strong>
                            <Tag :value="`${group.completed} / ${group.steps.length}`" :severity="group.completed === group.steps.length ? 'success' : 'info'" />
                        </div>
                        <div class="flex max-h-80 flex-col gap-3 overflow-auto">
                            <div v-for="step in group.steps" :key="step.name" class="flex items-start gap-3">
                                <Checkbox :model-value="step.status === 'completed'" binary disabled :aria-label="`${migrationLabel(step)}: ${migrationState(step.status)}`" class="mt-1 shrink-0" />
                                <div class="flex min-w-0 flex-1 flex-col gap-1">
                                    <span class="break-all font-mono text-xs">{{ migrationLabel(step) }}</span>
                                    <span class="flex items-center gap-2 text-xs" :class="step.status === 'failed' ? 'text-red-600 dark:text-red-300' : 'text-surface-500 dark:text-surface-300'">
                                        <i v-if="step.status === 'running'" class="pi pi-spin pi-spinner" aria-hidden="true" />
                                        {{ migrationState(step.status) }}
                                    </span>
                                    <span v-if="step.error" class="break-words text-xs text-red-600 dark:text-red-300">{{ step.error }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <template v-if="status.summary && !status.snapshot_pruned">
                    <p class="text-sm">To migawka z momentu porównania. Późniejsze zmiany bazy edytowalnej wymagają nowego porównania. Podgląd można zatwierdzić przez 24 godziny.</p>
                    <p class="font-semibold">Łącznie {{ changedCount }} zmian</p>
                    <DataTable :value="summary" scrollable scroll-height="18rem" size="small" class="text-sm">
                        <Column field="label" header="Obszar" />
                        <Column field="added" header="Dodane" />
                        <Column field="updated" header="Zmienione" />
                        <Column field="deleted" header="Usunięte" />
                    </DataTable>
                    <div class="flex flex-wrap gap-2">
                        <Dropdown v-model="table" :options="summary" option-label="label" option-value="name" placeholder="Wszystkie obszary" show-clear filter aria-label="Obszar zmian" />
                        <Dropdown v-model="type" :options="types" option-label="label" option-value="value" placeholder="Każdy rodzaj zmiany" show-clear aria-label="Rodzaj zmiany" />
                        <InputText v-model="search" placeholder="Szukaj nazwy lub ID" aria-label="Szukaj zmiany" />
                    </div>
                    <DataTable :value="changes" :loading="changesLoading" data-key="id" size="small" class="text-sm">
                        <template #empty>Brak zmian dla wybranych filtrów.</template>
                        <Column header="Obszar"><template #body="{ data }">{{ tableLabel(data.table_name) }}</template></Column>
                        <Column field="row_label" header="Obiekt" />
                        <Column header="Zmiana"><template #body="{ data }"><Tag :value="typeLabel(data.change_type)" :severity="severity(data.change_type)" /></template></Column>
                        <Column><template #body="{ data }"><Button label="Szczegóły" text size="small" @click="showDetail(data)" /></template></Column>
                    </DataTable>
                    <Paginator :rows="30" :total-records="total" :first="currentPage * 30" @page="currentPage = $event.page; loadChanges()" />
                </template>
                <Message v-if="status.snapshot_pruned" severity="warn" :closable="false">Szczegóły tej migawki wygasły. Przygotuj nowe porównanie.</Message>
                <template v-if="!running">
                    <Message v-if="confirmApply" severity="warn" :closable="false">Na pewno zaktualizować produkcję? Jej dane zostaną zastąpione sprawdzoną migawką template’u bazowego, łącznie z usunięciami widocznymi w diffie. Dane postaci i inne tabele gry nie są kopiowane.</Message>
                    <div class="flex flex-wrap justify-end gap-2">
                        <Button label="Nowe porównanie" severity="secondary" :loading="busy" @click="status = null; confirmApply = false" />
                        <Button v-if="canApply && !confirmApply" label="Przejrzano zmiany — kontynuuj" icon="pi pi-check" @click="confirmApply = true" />
                        <Button v-if="canApply && confirmApply" label="Tak, wykonaj aktualizację produkcji" icon="pi pi-upload" severity="danger" :loading="busy" @click="apply" />
                    </div>
                </template>
            </template>
            <template v-if="!running">
                <Message v-if="confirmMigrations" severity="warn" :closable="false">
                    Uruchomić brakujące migracje na template bazowym i produkcyjnym? Mogą one zmieniać schemat oraz dane i nie należą do transakcji kopiowania. Po ich zakończeniu automatycznie przygotujemy nowe porównanie. Zapis danych na produkcję wymaga osobnego zatwierdzenia.
                </Message>
                <div class="flex flex-wrap justify-end gap-2">
                    <Button v-if="!confirmMigrations" label="Sprawdź i uruchom migracje" icon="pi pi-database" severity="secondary" :disabled="busy" @click="confirmMigrations = true; confirmApply = false" />
                    <template v-else>
                        <Button label="Anuluj migracje" severity="secondary" text :disabled="busy" @click="confirmMigrations = false" />
                        <Button label="Tak, uruchom migracje" icon="pi pi-play" severity="warn" :loading="busy" @click="migrate" />
                    </template>
                </div>
            </template>
        </div>
    </Dialog>
    <Dialog v-model:visible="detailVisible" modal :header="selectedChange?.row_label ?? 'Szczegóły zmiany'" :style="{ width: '72rem', maxWidth: '94vw' }">
        <ProgressBar v-if="detailLoading" mode="indeterminate" />
        <div v-else-if="detail" class="flex flex-col gap-4">
            <div class="hidden gap-2 font-semibold md:grid md:grid-cols-2"><span>Produkcja przed aktualizacją</span><span>Migawka po aktualizacji</span></div>
            <div v-for="field in detail.changed_columns" :key="field" class="flex flex-col gap-2">
                <strong>{{ field }}</strong>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="text-xs font-medium md:hidden">Produkcja przed aktualizacją</span>
                        <pre class="m-0 max-h-72 overflow-auto whitespace-pre-wrap break-words rounded bg-red-50 p-3 text-sm text-red-950 dark:bg-red-950 dark:text-red-100">{{ format(detail.before?.[field]) }}</pre>
                    </div>
                    <div class="flex min-w-0 flex-col gap-1">
                        <span class="text-xs font-medium md:hidden">Migawka po aktualizacji</span>
                        <pre class="m-0 max-h-72 overflow-auto whitespace-pre-wrap break-words rounded bg-green-50 p-3 text-sm text-green-950 dark:bg-green-950 dark:text-green-100">{{ format(detail.after?.[field]) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
    </div>
</template>
