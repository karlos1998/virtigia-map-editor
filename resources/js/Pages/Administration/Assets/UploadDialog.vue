<script setup lang="ts">
import { computed, ref, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';

const props = defineProps<{ visible: boolean; path: string; maxUploadBytes: number }>();
const emit = defineEmits<{ 'update:visible': [boolean]; uploaded: [] }>();
type Upload = { file: File; progress: number; status: 'pending' | 'sending' | 'saving' | 'done' | 'failed'; error: string };
const queue = ref<Upload[]>([]);
const input = ref<HTMLInputElement>();
const running = ref(false);
const dragging = ref(false);
const stopped = ref(false);
let controller: AbortController | undefined;
const size = (bytes: number) => `${(bytes / 1024 / 1024).toLocaleString('pl-PL', { maximumFractionDigits: 1 })} MB`;
const complete = computed(() => queue.value.filter(item => item.status === 'done').length);
const progress = computed(() => {
    const total = queue.value.reduce((sum, item) => sum + item.file.size, 0);
    return total ? Math.round(queue.value.reduce((sum, item) => sum + item.file.size * item.progress, 0) / total) : 0;
});
const add = (files: FileList | File[]) => {
    for (const file of Array.from(files)) {
        if (queue.value.some(item => item.file.name === file.name && item.file.size === file.size)) continue;
        queue.value.push({ file, progress: 0, status: file.size > props.maxUploadBytes ? 'failed' : 'pending', error: file.size > props.maxUploadBytes ? 'Plik przekracza limit rozmiaru.' : '' });
    }
};
const selectFiles = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) add(target.files);
    target.value = '';
};
const drop = (event: DragEvent) => {
    dragging.value = false;
    if (!running.value && event.dataTransfer?.files) add(event.dataTransfer.files);
};
const stop = () => { stopped.value = true; controller?.abort(); };
const start = async () => {
    running.value = true;
    stopped.value = false;
    const destination = props.path;
    try {
        for (const item of queue.value) {
            if (stopped.value) break;
            if (item.status === 'done' || item.file.size > props.maxUploadBytes) continue;
            item.status = 'sending';
            item.progress = 0;
            item.error = '';
            controller = new AbortController();
            const form = new FormData();
            form.append('path', destination);
            form.append('file', item.file);
            try {
                await axios.post(route('administration.assets.uploads.store'), form, {
                    signal: controller.signal,
                    onUploadProgress: event => {
                        item.progress = event.total ? Math.round(event.loaded * 100 / event.total) : 0;
                        if (item.progress === 100) item.status = 'saving';
                    },
                });
                item.status = 'done';
                item.progress = 100;
            } catch (error: any) {
                item.status = 'failed';
                item.error = axios.isCancel(error) ? 'Przerwano. Odśwież katalog przed ponowieniem — serwer mógł już zapisać plik.'
                    : error.response?.status === 413 ? 'Serwer odrzucił plik: przekroczony limit wysyłania.'
                    : Object.values(error.response?.data?.errors ?? {}).flat()[0] as string || 'Nie udało się wysłać pliku. Spróbuj ponownie.';
            }
        }
    } finally {
        running.value = false;
        controller = undefined;
        emit('uploaded');
    }
};
onBeforeUnmount(stop);
</script>

<template>
    <Dialog :visible="visible" header="Wyślij pliki" modal :closable="!running" :close-on-escape="!running" class="w-[min(48rem,95vw)]" @update:visible="emit('update:visible', $event)">
        <div class="flex flex-col gap-5">
            <div class="text-surface-500 dark:text-surface-400">Do katalogu: <strong class="break-all">/{{ path }}</strong></div>
            <input ref="input" type="file" multiple class="hidden" @change="selectFiles" />
            <button type="button" :disabled="running" class="rounded-xl border-2 border-dashed p-8 text-center transition-colors" :class="dragging ? 'border-primary bg-primary-50 dark:bg-surface-800' : 'border-surface-300 dark:border-surface-600'" @click="input?.click()" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop">
                <i class="pi pi-cloud-upload mb-3 text-4xl text-primary" />
                <div class="font-semibold">Przeciągnij pliki tutaj lub wybierz z dysku</div>
                <div class="mt-2 text-sm text-surface-500">Maksymalnie {{ size(maxUploadBytes) }} na plik. Istniejące pliki nie zostaną nadpisane.</div>
            </button>
            <div v-if="queue.length" class="flex flex-col gap-3">
                <div class="flex justify-between text-sm"><span>Zapisano {{ complete }} z {{ queue.length }}</span><span>{{ progress }}%</span></div>
                <ProgressBar :value="progress" :show-value="false" />
                <div class="max-h-80 overflow-y-auto space-y-3">
                    <div v-for="(item, index) in queue" :key="index" class="rounded-lg border border-surface-200 dark:border-surface-700 p-3">
                        <div class="flex items-center gap-3">
                            <i :class="item.status === 'done' ? 'pi pi-check-circle text-green-500' : item.status === 'failed' ? 'pi pi-exclamation-circle text-red-500' : 'pi pi-file text-primary'" />
                            <div class="min-w-0 flex-1"><div class="truncate font-medium">{{ item.file.name }}</div><div class="text-xs text-surface-500">{{ size(item.file.size) }}</div></div>
                            <span class="text-sm">{{ item.status === 'saving' ? 'Zapisuję na S3…' : item.status === 'sending' ? `${item.progress}%` : item.status === 'done' ? 'Zapisano' : item.status === 'pending' ? 'W kolejce' : 'Błąd' }}</span>
                            <Button v-if="!running && item.status !== 'done'" icon="pi pi-times" text rounded aria-label="Usuń z kolejki" @click="queue.splice(index, 1)" />
                        </div>
                        <ProgressBar v-if="item.status === 'sending' || item.status === 'saving'" class="mt-3 h-1" :value="item.progress" :show-value="false" />
                        <Message v-if="item.error" severity="error" class="mt-2">{{ item.error }}</Message>
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
            <Button v-if="running" label="Przerwij wysyłanie" severity="secondary" @click="stop" />
            <template v-else>
                <Button label="Zamknij" severity="secondary" text @click="emit('update:visible', false)" />
                <Button v-if="complete === queue.length && queue.length" label="Wyczyść listę" severity="secondary" @click="queue = []" />
                <Button label="Wyślij pliki" icon="pi pi-upload" :disabled="!queue.some(item => item.status !== 'done' && item.file.size <= maxUploadBytes)" @click="start" />
            </template>
        </template>
    </Dialog>
</template>
