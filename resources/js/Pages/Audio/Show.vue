<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import ItemHeader from "@/Components/ItemHeader.vue";
import {route} from "ziggy-js";
import {useForm} from "@inertiajs/vue3";

const props = defineProps<{
    audio: {
        id: number
        name: string
        path: string
        url: string | null
    }
    linkedItems: any[]
}>();

const form = useForm({
    name: props.audio.name,
    file: null as File | null,
});

const save = () => {
    const payload = new FormData();
    payload.append('name', form.name || '');
    if (form.file) {
        payload.append('file', form.file);
    }
    payload.append('_method', 'PATCH');

    form.transform(() => payload as any).post(route('audios.update', { audio: props.audio.id }), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
};
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('audios.index')" route-back-label="Powrót do listy">
            <template #header>
                #{{ props.audio.id }} - {{ props.audio.name }}
            </template>
            <template #right-buttons>
                <button class="px-4 py-2 text-white bg-green-500 hover:bg-green-600 rounded shadow" @click="save">
                    <i class="pi pi-save mr-2"></i>
                    Zapisz
                </button>
            </template>
        </ItemHeader>

        <div class="card mb-4">
            <h4 class="font-semibold mb-2">Dane pliku</h4>
            <div class="mb-3">
                <label class="font-semibold block mb-2">Nazwa</label>
                <InputText v-model="form.name" class="w-full" />
            </div>
            <div class="mb-3">
                <label class="font-semibold block mb-2">Podmień plik audio (opcjonalnie)</label>
                <input type="file" accept="audio/*" @change="onFileChange" />
            </div>
            <div class="text-sm text-surface-500 mb-2">Ścieżka S3: <code>{{ props.audio.path }}</code></div>
            <audio v-if="props.audio.url" :src="props.audio.url" controls preload="none" class="w-full"></audio>
        </div>

        <div class="card">
            <h4 class="font-semibold mb-2">Powiązane przedmioty (audioId)</h4>
            <div v-if="!props.linkedItems.length" class="text-surface-500">Brak powiązanych przedmiotów dla tego audioId.</div>
            <ul v-else class="list-disc ml-5">
                <li v-for="item in props.linkedItems" :key="item.id">
                    [{{ item.id }}] {{ item.name }}
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
