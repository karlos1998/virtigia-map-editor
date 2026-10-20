<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layout/AppLayout.vue';
import UploadDialog from './UploadDialog.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Tree from 'primevue/tree';
import Menu from 'primevue/menu';
import Paginator from 'primevue/paginator';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';
import Skeleton from 'primevue/skeleton';
import type { TreeNode } from 'primevue/treenode';

const props = defineProps<{ initialPath: string; initialSearch: string; maxUploadBytes: number }>();
type Entry = { path: string; name: string; type: 'directory' | 'file'; size: number | null; modified: number | null; is_image: boolean; preview_url: string | null; thumbnail_url: string | null; thumbnailFailed?: boolean };
type Operation = { id: string; path: string; operation: string; status: string; progress: number; message: string; download_url: string | null };
const entries = ref<Entry[]>([]);
const loading = ref(false);
const error = ref('');
const notice = ref('');
const search = ref(props.initialSearch);
const page = ref(1);
const total = ref(0);
const perPage = ref(60);
const view = ref<'grid' | 'list'>('grid');
const treeNodes = ref<TreeNode[]>([{ key: 'root', label: 'Wszystkie pliki', icon: 'pi pi-home', data: '', leaf: false }]);
const expandedKeys = ref<Record<string, boolean>>({ root: true });
const treeError = ref('');
const uploadVisible = ref(false);
const preview = ref<Entry | null>(null);
const previewFailed = ref(false);
const dimensions = ref('');
const operations = ref<Operation[]>([]);
const operationsError = ref('');
const menu = ref<InstanceType<typeof Menu>>();
const selected = ref<Entry | null>(null);
const action = ref<'folder' | 'rename' | 'copy' | 'move' | 'delete' | null>(null);
const actionName = ref('');
const destination = ref('');
const confirmation = ref('');
const actionError = ref('');
const submitting = ref(false);
let listingAbort: AbortController | undefined;
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let pollTimer: ReturnType<typeof setTimeout> | undefined;
let destroyed = false;

const crumbs = computed(() => props.initialPath.split('/').filter(Boolean).map((label, index, parts) => ({ label, path: parts.slice(0, index + 1).join('/') })));
const title = computed(() => ({ folder: 'Nowy katalog', rename: 'Zmień nazwę', copy: 'Kopiuj', move: 'Przenieś', delete: 'Usuń' })[action.value ?? 'folder']);
const activeOperations = computed(() => operations.value.filter(item => ['queued', 'running'].includes(item.status)).length);
const formatSize = (bytes: number | null) => {
    if (bytes === null) return 'Katalog';
    if (bytes < 1024) return `${bytes} B`;
    const units = ['KB', 'MB', 'GB', 'TB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)) - 1, units.length - 1);
    return `${(bytes / 1024 ** (index + 1)).toLocaleString('pl-PL', { maximumFractionDigits: 1 })} ${units[index]}`;
};
const formatDate = (value: number | null) => value ? new Date(value * 1000).toLocaleString('pl-PL') : '—';
const message = (exception: any) => Object.values(exception.response?.data?.errors ?? {}).flat()[0] as string
    || (exception.response?.status === 404 ? 'Katalog lub plik już nie istnieje. Odśwież widok.' : exception.response?.status === 403 ? 'Brak uprawnień do tej operacji.' : 'Nie udało się wykonać operacji. Spróbuj ponownie.');
const downloadUrl = (entry: Entry) => route('administration.assets.download', { path: entry.path });
const join = (parent: string, name: string) => parent ? `${parent}/${name}` : name;

const loadListing = async (number = 1) => {
    listingAbort?.abort();
    const controller = new AbortController();
    listingAbort = controller;
    loading.value = true;
    entries.value = [];
    error.value = '';
    try {
        const { data } = await axios.get(route('administration.assets.listing'), { params: { path: props.initialPath, search: props.initialSearch, page: number }, signal: controller.signal });
        entries.value = data.entries;
        total.value = data.total;
        perPage.value = data.per_page;
        page.value = data.page;
        if (number > 1 && !data.entries.length && data.total) await loadListing(1);
    } catch (exception) {
        if (!axios.isCancel(exception)) error.value = message(exception);
    } finally {
        if (listingAbort === controller) loading.value = false;
    }
};
const navigate = (path: string, term = '') => {
    clearTimeout(searchTimer);
    router.get(route('administration.assets.index'), { path, ...(term ? { search: term } : {}) }, { preserveState: true, preserveScroll: true, only: ['initialPath', 'initialSearch'] });
};
watch(search, value => {
    clearTimeout(searchTimer);
    if (value === props.initialSearch) return;
    searchTimer = setTimeout(() => navigate(props.initialPath, value), 400);
});
watch(() => [props.initialPath, props.initialSearch], () => {
    search.value = props.initialSearch;
    void loadListing();
});
const expandNode = async (node: TreeNode, force = false) => {
    if (node.children && !force) return;
    node.loading = true;
    treeError.value = '';
    try {
        const { data } = await axios.get(route('administration.assets.listing'), { params: { path: node.data, directories_only: 1 } });
        node.children = data.entries.map((entry: Entry) => ({ key: entry.path, label: entry.name, data: entry.path, icon: 'pi pi-folder', leaf: false }));
        node.leaf = node.children?.length === 0;
    } catch (exception) {
        treeError.value = message(exception);
    } finally { node.loading = false; }
};
const reloadTree = async () => {
    expandedKeys.value = { root: true };
    await expandNode(treeNodes.value[0], true);
};
const refresh = () => { void loadListing(page.value); void reloadTree(); };
const openEntry = (entry: Entry) => {
    if (entry.type === 'directory') navigate(entry.path);
    else { preview.value = entry; previewFailed.value = false; dimensions.value = ''; }
};
const imageLoaded = (event: Event) => {
    const image = event.target as HTMLImageElement;
    dimensions.value = `${image.naturalWidth} × ${image.naturalHeight} px`;
};
const openAction = (mode: typeof action.value, entry: Entry | null = null) => {
    selected.value = entry;
    action.value = mode;
    actionName.value = entry?.name ?? '';
    destination.value = entry?.path ?? '';
    confirmation.value = '';
    actionError.value = '';
};
const copyPath = async () => {
    try { await navigator.clipboard.writeText(selected.value?.path ?? props.initialPath); notice.value = 'Ścieżka skopiowana.'; }
    catch { notice.value = 'Nie udało się skopiować ścieżki do schowka.'; }
};
const menuItems = computed(() => [
    { label: 'Otwórz', icon: 'pi pi-eye', command: () => selected.value && openEntry(selected.value) },
    { label: 'Pobierz', icon: 'pi pi-download', command: () => selected.value && download(selected.value) },
    { separator: true },
    { label: 'Zmień nazwę', icon: 'pi pi-pencil', command: () => openAction('rename', selected.value) },
    { label: 'Przenieś', icon: 'pi pi-folder-open', command: () => openAction('move', selected.value) },
    { label: 'Kopiuj', icon: 'pi pi-copy', command: () => openAction('copy', selected.value) },
    { label: 'Kopiuj ścieżkę', icon: 'pi pi-link', command: copyPath },
    { separator: true },
    { label: 'Usuń', icon: 'pi pi-trash', command: () => openAction('delete', selected.value) },
]);
const showMenu = (event: Event, entry: Entry) => { selected.value = entry; menu.value?.toggle(event); };
const loadOperations = async () => {
    clearTimeout(pollTimer);
    if (destroyed) return;
    try {
        const { data } = await axios.get<Operation[]>(route('administration.assets.operations.index'));
        const finished = data.some(item => ['completed', 'failed'].includes(item.status) && operations.value.some(old => old.id === item.id && ['running', 'queued'].includes(old.status)));
        operations.value = data;
        operationsError.value = '';
        if (finished) refresh();
    } catch { operationsError.value = 'Nie udało się odczytać postępu operacji.'; }
    if (!destroyed && (activeOperations.value || operationsError.value)) pollTimer = setTimeout(loadOperations, 2500);
};
const startOperation = async (entry: Entry, operation: string, target?: string) => {
    const { data } = await axios.post<Operation>(route('administration.assets.operations.store'), { path: entry.path, type: entry.type, operation, destination: target });
    operations.value = [data, ...operations.value.filter(item => item.id !== data.id)];
    void loadOperations();
};
const download = async (entry: Entry) => {
    if (entry.type === 'file') { window.location.assign(downloadUrl(entry)); return; }
    try { await startOperation(entry, 'archive'); notice.value = 'Przygotowuję ZIP. Przycisk pobierania pojawi się w sekcji operacji poniżej.'; }
    catch (exception) { error.value = message(exception); }
};
const submitAction = async () => {
    submitting.value = true;
    actionError.value = '';
    try {
        if (action.value === 'folder') {
            await axios.post(route('administration.assets.directories.store'), { path: props.initialPath, name: actionName.value });
            notice.value = 'Katalog został utworzony.';
            refresh();
        } else if (selected.value) {
            let target = destination.value;
            if (action.value === 'rename') target = join(selected.value.path.split('/').slice(0, -1).join('/'), actionName.value);
            await startOperation(selected.value, action.value === 'rename' ? 'move' : action.value!, target);
            notice.value = 'Operacja została dodana do kolejki. Postęp znajdziesz poniżej.';
        }
        action.value = null;
    } catch (exception) { actionError.value = message(exception); }
    finally { submitting.value = false; }
};
const chooseDestination = (node: TreeNode) => { destination.value = join(node.data, selected.value?.name ?? ''); };
onMounted(() => { void loadListing(); void reloadTree(); void loadOperations(); });
onBeforeUnmount(() => { destroyed = true; listingAbort?.abort(); clearTimeout(searchTimer); clearTimeout(pollTimer); });
</script>

<template>
    <AppLayout>
        <Head title="Grafiki i pliki S3" />
        <div class="flex flex-col gap-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><h1 class="m-0 text-2xl font-semibold">Grafiki i pliki S3</h1><p class="mt-2 mb-0 text-surface-500 dark:text-surface-400">Wspólna biblioteka zasobów wszystkich światów.</p></div>
                <div class="flex gap-2"><Button label="Nowy katalog" icon="pi pi-folder-plus" severity="secondary" @click="openAction('folder')" /><Button label="Wyślij pliki" icon="pi pi-upload" @click="uploadVisible = true" /></div>
            </div>
            <Message v-if="notice" severity="success" closable @close="notice = ''">{{ notice }}</Message>
            <div class="grid grid-cols-1 lg:grid-cols-[16rem_minmax(0,1fr)] gap-5">
                <aside class="card mb-0 min-w-0 self-start lg:sticky lg:top-24">
                    <div class="mb-3 flex items-center justify-between"><strong>Katalogi</strong><Button icon="pi pi-refresh" text rounded aria-label="Odśwież katalogi" @click="reloadTree" /></div>
                    <Message v-if="treeError" severity="error">{{ treeError }}</Message>
                    <Tree v-model:expanded-keys="expandedKeys" :value="treeNodes" selection-mode="single" class="p-0 max-h-96 lg:max-h-[65vh] overflow-auto" loading-mode="icon" @node-expand="expandNode" @node-select="node => navigate(node.data)" />
                </aside>
                <section class="card mb-0 min-w-0">
                    <nav aria-label="Ścieżka katalogu" class="mb-5 flex flex-wrap items-center gap-2 text-sm">
                        <button class="text-primary hover:underline" @click="navigate('')"><i class="pi pi-home" /> Wszystkie pliki</button>
                        <template v-for="crumb in crumbs" :key="crumb.path"><i class="pi pi-angle-right text-surface-400" /><button class="break-all hover:underline" @click="navigate(crumb.path)">{{ crumb.label }}</button></template>
                    </nav>
                    <div class="mb-5 flex flex-wrap items-center gap-3">
                        <div class="relative min-w-48 flex-1"><i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400" /><InputText v-model="search" class="w-full pl-10" placeholder="Szukaj po nazwie w tym katalogu i podkatalogach…" aria-label="Szukaj plików po nazwie" /></div>
                        <Button icon="pi pi-refresh" severity="secondary" outlined aria-label="Odśwież pliki" :loading="loading" @click="refresh" />
                        <div class="flex gap-1"><Button icon="pi pi-th-large" :text="view !== 'grid'" severity="secondary" aria-label="Widok miniaturek" :aria-pressed="view === 'grid'" @click="view = 'grid'" /><Button icon="pi pi-list" :text="view !== 'list'" severity="secondary" aria-label="Widok listy" :aria-pressed="view === 'list'" @click="view = 'list'" /></div>
                    </div>
                    <div v-if="initialSearch" class="mb-4 flex flex-wrap justify-between gap-2 text-sm text-surface-500"><span>Wyniki dla „{{ initialSearch }}” we wszystkich podkatalogach</span><button class="text-primary" @click="search = ''">Wyczyść wyszukiwanie</button></div>
                    <Message v-if="error" severity="error" class="mb-4">{{ error }} <Button label="Ponów" text @click="refresh" /></Message>
                    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4" aria-label="Ładowanie plików"><Skeleton v-for="index in 8" :key="index" height="12rem" /></div>
                    <div v-else-if="!entries.length && !error" class="py-16 text-center text-surface-500"><i class="pi pi-folder-open text-5xl mb-4" /><h2 class="text-lg">{{ initialSearch ? 'Brak pasujących plików' : 'Ten katalog jest pusty' }}</h2><p>{{ initialSearch ? 'Zmień nazwę lub przejdź do katalogu nadrzędnego.' : 'Wyślij pierwsze pliki lub utwórz podkatalog.' }}</p></div>
                    <div v-else :class="view === 'grid' ? 'grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3' : 'flex flex-col gap-2'">
                        <article v-for="entry in entries" :key="entry.path" class="group rounded-xl border border-surface-200 dark:border-surface-700 hover:border-primary transition-colors overflow-hidden" :class="view === 'list' ? 'flex items-center gap-3 p-2' : 'flex flex-col'">
                            <button :aria-label="`Otwórz ${entry.name}`" :title="entry.path" class="flex items-center justify-center bg-surface-50 dark:bg-surface-800 shrink-0" :class="view === 'grid' ? 'h-36 w-full p-4' : 'h-14 w-16 rounded-lg p-1'" @click="openEntry(entry)">
                                <i v-if="entry.type === 'directory'" class="pi pi-folder text-5xl text-amber-500" />
                                <img v-else-if="entry.is_image && !entry.thumbnailFailed" :src="entry.thumbnail_url!" :alt="entry.name" loading="lazy" class="max-h-full max-w-full object-contain" @error="entry.thumbnailFailed = true" />
                                <i v-else class="pi pi-file text-4xl text-surface-400" />
                            </button>
                            <div class="min-w-0 flex-1" :class="view === 'grid' ? 'px-3 pt-3' : ''"><button class="block w-full truncate text-left font-medium hover:text-primary" :title="entry.path" @click="openEntry(entry)">{{ entry.name }}</button><div class="mt-1 text-xs text-surface-500 dark:text-surface-400">{{ formatSize(entry.size) }}</div><div v-if="initialSearch" class="mt-1 truncate text-xs text-surface-500" :title="entry.path">{{ entry.path }}</div></div>
                            <div class="flex items-center justify-end gap-1" :class="view === 'grid' ? 'px-2 pb-2' : ''"><Button icon="pi pi-download" text rounded size="small" :aria-label="`Pobierz ${entry.name}`" @click="download(entry)" /><Button icon="pi pi-ellipsis-v" text rounded size="small" :aria-label="`Akcje dla ${entry.name}`" aria-haspopup="true" aria-controls="asset-actions" @click="showMenu($event, entry)" /></div>
                        </article>
                    </div>
                    <div v-if="!loading" class="mt-5 flex flex-wrap justify-between items-center gap-3 text-sm text-surface-500"><span>{{ total }} elementów</span><Paginator v-if="total > perPage" :first="(page - 1) * perPage" :rows="perPage" :total-records="total" @page="event => loadListing(event.page + 1)" /></div>
                </section>
            </div>
            <section v-if="operations.length || operationsError" class="card mb-0">
                <div class="mb-4 flex justify-between items-center"><h2 class="m-0 text-lg font-semibold">Operacje <span v-if="activeOperations" class="text-primary">({{ activeOperations }} w toku)</span></h2><Button label="Odśwież" text icon="pi pi-refresh" @click="loadOperations" /></div>
                <Message v-if="operationsError" severity="error">{{ operationsError }}</Message>
                <div class="max-h-72 overflow-auto space-y-3">
                    <div v-for="operation in operations" :key="operation.id" class="rounded-lg bg-surface-50 dark:bg-surface-800 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-3"><div class="min-w-0"><strong>{{ ({ archive: 'Archiwum ZIP', move: 'Przenoszenie / zmiana nazwy', copy: 'Kopiowanie', delete: 'Usuwanie' })[operation.operation] }}</strong><div class="text-sm break-all text-surface-500">{{ operation.path }}</div></div><a v-if="operation.download_url" :href="operation.download_url" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-primary-contrast"><i class="pi pi-download" /> Pobierz ZIP</a><i v-else-if="operation.status === 'completed'" class="pi pi-check-circle text-green-500" /></div>
                        <div class="mt-2 text-sm" :class="operation.status === 'failed' ? 'text-red-500' : 'text-surface-500'">{{ operation.message }}</div>
                        <ProgressBar v-if="['queued', 'running'].includes(operation.status)" :value="operation.progress" class="mt-2 h-2" :show-value="false" />
                    </div>
                </div>
                <p class="mt-3 mb-0 text-xs text-surface-500">Archiwa są dostępne przez 24 godziny. Operacje w tle trwają także po zamknięciu strony.</p>
            </section>
        </div>
        <Menu id="asset-actions" ref="menu" :model="menuItems" popup />
        <UploadDialog v-model:visible="uploadVisible" :path="initialPath" :max-upload-bytes="maxUploadBytes" @uploaded="refresh" />
        <Dialog :visible="!!preview" :header="preview?.name" modal maximizable class="w-[min(70rem,96vw)]" @update:visible="preview = null">
            <div v-if="preview" class="flex flex-col gap-4">
                <div class="rounded-xl bg-surface-100 dark:bg-surface-900 min-h-48 flex justify-center items-center p-5 overflow-auto">
                    <img v-if="preview.is_image && !previewFailed" :src="preview.preview_url!" :alt="preview.name" class="max-h-[65vh] max-w-full object-contain" @load="imageLoaded" @error="previewFailed = true" />
                    <div v-else class="text-center text-surface-500"><i class="pi pi-file text-5xl mb-3" /><p>{{ previewFailed ? 'Nie udało się wczytać podglądu.' : 'Podgląd jest dostępny dla plików graficznych.' }}</p></div>
                </div>
                <div class="flex flex-wrap gap-4 text-sm text-surface-500"><span>{{ formatSize(preview.size) }}</span><span v-if="dimensions">{{ dimensions }}</span><span>{{ formatDate(preview.modified) }}</span></div>
                <div class="break-all text-sm">{{ preview.path }}</div>
                <a :href="downloadUrl(preview)" class="self-start inline-flex gap-2 items-center text-primary"><i class="pi pi-download" /> Pobierz oryginał</a>
            </div>
        </Dialog>
        <Dialog :visible="!!action" :header="title" modal :closable="!submitting" :close-on-escape="!submitting" class="w-[min(38rem,95vw)]" @update:visible="action = null">
            <form class="flex flex-col gap-4" @submit.prevent="submitAction">
                <div v-if="selected" class="break-all text-sm text-surface-500">{{ selected.path }}</div>
                <Message v-if="action === 'rename' || action === 'move' || action === 'delete'" severity="warn">{{ action === 'delete' ? 'Usunięcie jest trwałe.' : 'Zmiana ścieżki pliku nie aktualizuje odwołań zapisanych w grze.' }} Jeśli zasób jest używany przez mapy, NPC lub przedmioty, ich grafika może przestać się wyświetlać.</Message>
                <template v-if="action === 'folder' || action === 'rename'"><label for="asset-name" class="font-medium">Nazwa</label><InputText id="asset-name" v-model="actionName" autofocus required :disabled="submitting" /></template>
                <template v-if="action === 'move' || action === 'copy'"><label for="asset-destination" class="font-medium">Docelowa ścieżka wraz z nazwą</label><InputText id="asset-destination" v-model="destination" required :disabled="submitting" /><span class="text-sm text-surface-500">Wpisz ścieżkę lub wybierz katalog poniżej. Możesz też zmienić nazwę kopii.</span><Tree :value="treeNodes" selection-mode="single" class="max-h-56 overflow-auto" @node-expand="expandNode" @node-select="chooseDestination" /></template>
                <template v-if="action === 'delete'"><p class="m-0">{{ selected?.type === 'directory' ? 'Usunąć katalog wraz ze wszystkimi podkatalogami i plikami?' : 'Usunąć ten plik?' }}</p><template v-if="selected?.type === 'directory'"><label for="asset-confirmation">Aby potwierdzić, wpisz nazwę katalogu: <strong>{{ selected.name }}</strong></label><InputText id="asset-confirmation" v-model="confirmation" autocomplete="off" /></template></template>
                <Message v-if="actionError" severity="error">{{ actionError }}</Message>
                <div class="flex justify-end gap-2 mt-2"><Button label="Anuluj" severity="secondary" text :disabled="submitting" @click="action = null" /><Button type="submit" :label="title" :severity="action === 'delete' ? 'danger' : 'primary'" :loading="submitting" :disabled="action === 'delete' && selected?.type === 'directory' && confirmation !== selected.name" /></div>
            </form>
        </Dialog>
    </AppLayout>
</template>
