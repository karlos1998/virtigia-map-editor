<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layout/AppLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Message from 'primevue/message';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

type WorldTemplate = {
    value: string;
    label: string;
};

type DumpStatusName = 'idle' | 'queued' | 'estimating' | 'dumping' | 'ready' | 'downloaded' | 'failed';

type DumpStatus = {
    id: string | null;
    world: string;
    status: DumpStatusName;
    progress: number | null;
    message: string;
    filename: string | null;
    dumped_bytes: number;
    estimated_bytes: number | null;
    file_size: number;
    queued_at: string | null;
    started_at: string | null;
    updated_at: string | null;
    finished_at: string | null;
    failed_at: string | null;
    downloaded_at: string | null;
    expires_at: string | null;
};

type TransferState = {
    active: boolean;
    progress: number;
    receivedBytes: number;
    totalBytes: number;
    message: string;
    failed: boolean;
};

const props = defineProps<{
    worlds: WorldTemplate[];
    statuses: Record<string, DumpStatus>;
}>();

const statuses = reactive<Record<string, DumpStatus>>({ ...props.statuses });
const transfers = reactive<Record<string, TransferState>>({});
const autoDownloadWorlds = new Set<string>();
const autoDownloadingDumpIds = new Set<string>();
let statusPoll: number | undefined;

const activeJobs = computed(() => {
    return Object.values(statuses).filter((status) => isPreparingStatus(status)).length;
});

const transferFor = (world: string): TransferState => {
    transfers[world] ??= {
        active: false,
        progress: 0,
        receivedBytes: 0,
        totalBytes: 0,
        message: '',
        failed: false,
    };

    return transfers[world];
};

const statusFor = (world: string): DumpStatus => {
    statuses[world] ??= {
        id: null,
        world,
        status: 'idle',
        progress: 0,
        message: 'Gotowy do przygotowania',
        filename: null,
        dumped_bytes: 0,
        estimated_bytes: null,
        file_size: 0,
        queued_at: null,
        started_at: null,
        updated_at: null,
        finished_at: null,
        failed_at: null,
        downloaded_at: null,
        expires_at: null,
    };

    return statuses[world];
};

const isPreparingStatus = (status: DumpStatus): boolean => {
    return ['queued', 'estimating', 'dumping'].includes(status.status);
};

const formatBytes = (bytes: number | null): string => {
    if (!bytes || bytes <= 0) {
        return '0 MB';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / Math.pow(1024, exponent);

    return `${value.toLocaleString('pl-PL', { maximumFractionDigits: exponent === 0 ? 0 : 1 })} ${units[exponent]}`;
};

const generatedBytes = (status: DumpStatus): number => {
    return status.dumped_bytes || status.file_size || 0;
};

const statusPrimarySizeLabel = (status: DumpStatus): string => {
    if (status.status === 'ready' || status.status === 'downloaded') {
        return formatBytes(status.file_size || generatedBytes(status));
    }

    const bytes = generatedBytes(status);

    if (bytes > 0) {
        return `${formatBytes(bytes)} wygenerowane`;
    }

    return formatBytes(bytes);
};

const statusEstimatedSizeLabel = (status: DumpStatus): string | null => {
    if (!isPreparingStatus(status) || !status.estimated_bytes || status.estimated_bytes <= 0) {
        return null;
    }

    return `szac. ${formatBytes(status.estimated_bytes)}`;
};

const statusLabel = (status: DumpStatus): string => {
    if (status.status === 'queued') {
        return 'W kolejce';
    }

    if (status.status === 'estimating') {
        return 'Analiza';
    }

    if (status.status === 'dumping') {
        return 'W trakcie';
    }

    if (status.status === 'ready') {
        return 'Gotowe';
    }

    if (status.status === 'downloaded') {
        return 'Pobrane';
    }

    if (status.status === 'failed') {
        return 'Błąd';
    }

    return 'Gotowy';
};

const severityFor = (status: DumpStatus): 'success' | 'danger' | 'info' | 'secondary' | 'warn' => {
    if (status.status === 'ready' || status.status === 'downloaded') {
        return 'success';
    }

    if (status.status === 'failed') {
        return 'danger';
    }

    if (isPreparingStatus(status)) {
        return 'info';
    }

    return 'secondary';
};

const actionLabel = (world: string): string => {
    const status = statusFor(world);
    const transfer = transferFor(world);

    if (transfer.active) {
        return 'Pobieranie';
    }

    if (isPreparingStatus(status)) {
        return 'Przygotowuję';
    }

    if (status.status === 'ready') {
        return 'Pobierz plik';
    }

    if (status.status === 'failed') {
        return 'Spróbuj ponownie';
    }

    return 'Przygotuj dump';
};

const actionIcon = (world: string): string => {
    const status = statusFor(world);
    const transfer = transferFor(world);

    if (transfer.active || isPreparingStatus(status)) {
        return 'pi pi-spin pi-spinner';
    }

    if (status.status === 'ready') {
        return 'pi pi-download';
    }

    return 'pi pi-play';
};

const canUseAction = (world: string): boolean => {
    return !transferFor(world).active && !isPreparingStatus(statusFor(world));
};

const primaryProgress = (world: string): number => {
    const status = statusFor(world);

    return status.progress ?? 0;
};

const shouldShowIndeterminate = (world: string): boolean => {
    const status = statusFor(world);

    return isPreparingStatus(status) && status.progress === null;
};

const filenameFromDisposition = (disposition: string | null, fallback: string): string => {
    if (!disposition) {
        return fallback;
    }

    const encodedFilename = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];

    if (encodedFilename) {
        return decodeURIComponent(encodedFilename.replaceAll('"', ''));
    }

    const filename = disposition.match(/filename="?([^";]+)"?/i)?.[1];

    return filename ?? fallback;
};

const saveBlob = (blob: Blob, filename: string): void => {
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = objectUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();

    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
};

const errorMessageFor = async (response: Response): Promise<string> => {
    if (response.status === 403) {
        return 'Brak uprawnień administratora.';
    }

    if (response.status === 404) {
        return 'Plik nie jest gotowy albo wygasł.';
    }

    return 'Nie udało się pobrać dumpa bazy danych.';
};

const worldTemplateFor = (world: string): WorldTemplate | null => {
    return props.worlds.find((worldTemplate) => worldTemplate.value === world) ?? null;
};

const applyStatus = (world: string, status: DumpStatus): void => {
    statuses[world] = status;

    if (isPreparingStatus(status)) {
        autoDownloadWorlds.add(world);
    }

    if (['idle', 'downloaded', 'failed'].includes(status.status)) {
        autoDownloadWorlds.delete(world);
    }
};

const refreshStatus = async (world: string): Promise<void> => {
    const { data } = await axios.get<DumpStatus>(route('administration.database-dumps.status', { world }));
    applyStatus(world, data);
    await maybeDownloadPreparedDump(world);
};

const refreshAllStatuses = async (): Promise<void> => {
    await Promise.all(props.worlds.map((world) => refreshStatus(world.value)));
};

const startDump = async (world: string): Promise<void> => {
    autoDownloadWorlds.add(world);
    Object.assign(transferFor(world), {
        active: false,
        progress: 0,
        receivedBytes: 0,
        totalBytes: 0,
        message: '',
        failed: false,
    });

    const { data } = await axios.post<DumpStatus>(route('administration.database-dumps.start', { world }));
    applyStatus(world, data);
    await maybeDownloadPreparedDump(world);
};

const downloadPreparedDump = async (world: WorldTemplate): Promise<void> => {
    const status = statusFor(world.value);

    if (!status.id) {
        return;
    }

    const transfer = transferFor(world.value);

    Object.assign(transfer, {
        active: true,
        progress: 0,
        receivedBytes: 0,
        totalBytes: 0,
        message: 'Pobieram gotowy plik',
        failed: false,
    });

    try {
        const response = await fetch(route('administration.database-dumps.download', {
            world: world.value,
            dump: status.id,
        }), {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(await errorMessageFor(response));
        }

        const totalBytes = Number(response.headers.get('content-length') ?? 0);
        const filename = filenameFromDisposition(
            response.headers.get('content-disposition'),
            status.filename ?? `${world.value}-database.sql`,
        );

        transfer.totalBytes = Number.isFinite(totalBytes) ? totalBytes : 0;

        if (!response.body) {
            const blob = await response.blob();

            saveBlob(blob, filename);
            Object.assign(transfer, {
                active: false,
                progress: 100,
                receivedBytes: blob.size,
                totalBytes: blob.size,
                message: 'Plik pobrany',
            });
            await refreshStatus(world.value);

            return;
        }

        const reader = response.body.getReader();
        const chunks: Uint8Array[] = [];
        let receivedBytes = 0;

        while (true) {
            const { done, value } = await reader.read();

            if (done) {
                break;
            }

            if (!value) {
                continue;
            }

            chunks.push(value);
            receivedBytes += value.length;
            transfer.receivedBytes = receivedBytes;

            if (transfer.totalBytes > 0) {
                transfer.progress = Math.min(99, Math.round((receivedBytes / transfer.totalBytes) * 100));
            }
        }

        const blob = new Blob(chunks, {
            type: response.headers.get('content-type') ?? 'application/sql',
        });

        saveBlob(blob, filename);
        Object.assign(transfer, {
            active: false,
            progress: 100,
            receivedBytes,
            totalBytes: transfer.totalBytes || receivedBytes,
            message: 'Plik pobrany',
        });
        await refreshStatus(world.value);
    } catch (error) {
        Object.assign(transfer, {
            active: false,
            failed: true,
            progress: 0,
            message: error instanceof Error ? error.message : 'Nie udało się pobrać dumpa.',
        });
    }
};

const maybeDownloadPreparedDump = async (world: string): Promise<void> => {
    const status = statusFor(world);

    if (status.status !== 'ready' || !status.id || !autoDownloadWorlds.has(world)) {
        return;
    }

    if (transferFor(world).active || autoDownloadingDumpIds.has(status.id)) {
        return;
    }

    const worldTemplate = worldTemplateFor(world);

    if (!worldTemplate) {
        return;
    }

    autoDownloadWorlds.delete(world);
    autoDownloadingDumpIds.add(status.id);

    try {
        await downloadPreparedDump(worldTemplate);
    } finally {
        autoDownloadingDumpIds.delete(status.id);
    }
};

const handleAction = async (world: WorldTemplate): Promise<void> => {
    const status = statusFor(world.value);

    if (status.status === 'ready') {
        await downloadPreparedDump(world);

        return;
    }

    await startDump(world.value);
};

onMounted(() => {
    props.worlds.forEach((world) => {
        if (isPreparingStatus(statusFor(world.value))) {
            autoDownloadWorlds.add(world.value);
        }
    });

    statusPoll = window.setInterval(() => {
        void refreshAllStatuses();
    }, 2000);
});

onUnmounted(() => {
    if (statusPoll !== undefined) {
        window.clearInterval(statusPoll);
    }
});
</script>

<template>
    <AppLayout title="Dump bazy danych">
        <div class="space-y-6">
            <section class="border-b border-surface-200 pb-5 dark:border-surface-700">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-emerald-500 text-white shadow-sm">
                            <i class="pi pi-database text-xl"></i>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-semibold text-surface-950 dark:text-surface-0">Dump bazy danych</h1>
                                <Tag :value="activeJobs > 0 ? `${activeJobs} w toku` : 'Administracja'" severity="info" />
                            </div>
                            <div class="mt-1 text-sm text-surface-500 dark:text-surface-400">Template'y światów</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card
                    v-for="world in props.worlds"
                    :key="world.value"
                    class="overflow-hidden"
                >
                    <template #content>
                        <div class="flex h-full flex-col gap-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-surface-200 bg-surface-50 text-surface-700 dark:border-surface-700 dark:bg-surface-800 dark:text-surface-100">
                                        <i class="pi pi-server text-lg"></i>
                                    </div>

                                    <div>
                                        <h2 class="text-lg font-semibold text-surface-950 dark:text-surface-0">{{ world.label }}</h2>
                                        <div class="text-sm text-surface-500 dark:text-surface-400">
                                            {{ statusFor(world.value).filename ?? `${world.value}_database.sql` }}
                                        </div>
                                    </div>
                                </div>

                                <Tag :value="statusLabel(statusFor(world.value))" :severity="severityFor(statusFor(world.value))" />
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-surface-700 dark:text-surface-100">
                                        {{ statusFor(world.value).message }}
                                    </span>
                                    <span class="shrink-0 text-right text-surface-500 dark:text-surface-400">
                                        <span class="block">{{ statusPrimarySizeLabel(statusFor(world.value)) }}</span>
                                        <span v-if="statusEstimatedSizeLabel(statusFor(world.value))" class="block text-xs">
                                            {{ statusEstimatedSizeLabel(statusFor(world.value)) }}
                                        </span>
                                    </span>
                                </div>

                                <ProgressBar
                                    v-if="shouldShowIndeterminate(world.value)"
                                    mode="indeterminate"
                                    class="h-2 overflow-hidden rounded-full"
                                />
                                <ProgressBar
                                    v-else
                                    :value="primaryProgress(world.value)"
                                    :show-value="false"
                                    class="h-2 overflow-hidden rounded-full"
                                />
                            </div>

                            <div v-if="transferFor(world.value).active || transferFor(world.value).progress > 0 || transferFor(world.value).failed" class="space-y-3">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-surface-700 dark:text-surface-100">
                                        {{ transferFor(world.value).message }}
                                    </span>
                                    <span class="shrink-0 text-surface-500 dark:text-surface-400">
                                        {{ formatBytes(transferFor(world.value).receivedBytes) }}
                                        <template v-if="transferFor(world.value).totalBytes > 0">
                                            / {{ formatBytes(transferFor(world.value).totalBytes) }}
                                        </template>
                                    </span>
                                </div>

                                <ProgressBar
                                    :value="transferFor(world.value).progress"
                                    :show-value="false"
                                    class="h-2 overflow-hidden rounded-full"
                                />
                            </div>

                            <Message
                                v-if="statusFor(world.value).status === 'failed'"
                                severity="error"
                                :closable="false"
                            >
                                {{ statusFor(world.value).message }}
                            </Message>

                            <Message
                                v-if="transferFor(world.value).failed"
                                severity="error"
                                :closable="false"
                            >
                                {{ transferFor(world.value).message }}
                            </Message>

                            <div class="flex justify-end">
                                <Button
                                    :label="actionLabel(world.value)"
                                    :icon="actionIcon(world.value)"
                                    :disabled="!canUseAction(world.value)"
                                    @click="handleAction(world)"
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
