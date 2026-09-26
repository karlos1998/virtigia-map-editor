<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import ItemHeader from '@/Components/ItemHeader.vue';
import MapImageCropper, { type MapImageExport } from '@/Pages/Map/Components/MapImageCropper.vue';
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

type MapImageCropperExpose = {
    exportImage: () => Promise<MapImageExport | null>;
};

const cropper = ref<MapImageCropperExpose | null>(null);
const imageReady = ref(false);
const exportError = ref('');
const isPreparing = ref(false);
const preparedImageSize = ref(0);

const form = useForm<{ image: File | null; name: string; fileName: string }>({
    image: null,
    name: '',
    fileName: '',
});

const uploadPercentage = computed(() => form.progress?.percentage ?? 0);

function formatBytes(bytes: number): string {
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

const submit = async (): Promise<void> => {
    exportError.value = '';
    isPreparing.value = true;

    try {
        const image = await cropper.value?.exportImage();

        if (!image) {
            exportError.value = 'Wybierz i przygotuj grafikę mapy przed zapisaniem.';

            return;
        }

        form.image = image.file;
        form.fileName = image.fileName;
        preparedImageSize.value = image.size;
        form.post(route('maps.store'), {
            preserveScroll: true,
            forceFormData: true,
        });
    } catch (error) {
        exportError.value = error instanceof Error ? error.message : 'Nie udało się przygotować grafiki mapy.';
    } finally {
        isPreparing.value = false;
    }
};
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('maps.index')">
            <template #header>
                Tworzenie nowej mapy
            </template>
        </ItemHeader>

        <div class="card flex flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Przygotuj grafikę</h1>
                <p class="mt-1 text-gray-600">
                    Ustal rozmiar mapy w polach, przeskaluj grafikę i przeciągnij ją pod siatką, aby wybrać kadr.
                </p>
            </div>

            <MapImageCropper ref="cropper" @ready-change="imageReady = $event" />

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                <label for="map-name" class="mb-2 block text-sm font-medium text-gray-700">Nazwa mapy</label>
                <InputText id="map-name" v-model="form.name" class="w-full" placeholder="Np. Stare podziemia" />

                <div class="mt-3 flex flex-col gap-2">
                    <Message v-if="form.errors.name" severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
                    <Message v-if="form.errors.image" severity="error" size="small" variant="simple">{{ form.errors.image }}</Message>
                    <Message v-if="form.errors.fileName" severity="error" size="small" variant="simple">{{ form.errors.fileName }}</Message>
                    <Message v-if="exportError" severity="error" size="small" variant="simple">{{ exportError }}</Message>
                </div>

                <div v-if="isPreparing || form.processing" class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="mb-2 flex items-center justify-between gap-3 text-sm text-blue-950">
                        <span v-if="isPreparing">Optymalizuję grafikę w przeglądarce…</span>
                        <span v-else-if="uploadPercentage < 100">Wysyłanie {{ formatBytes(preparedImageSize) }}…</span>
                        <span v-else>Grafika wysłana. Serwer kończy optymalizację i zapis mapy…</span>
                        <span v-if="!isPreparing && uploadPercentage < 100" class="font-semibold tabular-nums">{{ uploadPercentage }}%</span>
                    </div>
                    <ProgressBar
                        v-if="!isPreparing && uploadPercentage < 100"
                        :value="uploadPercentage"
                        :show-value="false"
                        class="h-3"
                    />
                    <ProgressBar v-else mode="indeterminate" class="h-3" />
                </div>

                <div class="mt-5 flex justify-end">
                    <Button
                        type="button"
                        icon="pi pi-check"
                        label="Utwórz mapę"
                        :disabled="!imageReady || isPreparing || form.processing"
                        :loading="isPreparing || form.processing"
                        @click="submit"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
