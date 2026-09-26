<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import ItemHeader from '@/Components/ItemHeader.vue';
import MapImageCropper, { type MapImageExport } from '@/Pages/Map/Components/MapImageCropper.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

type MapImageCropperExpose = {
    exportImage: () => Promise<MapImageExport | null>;
};

const cropper = ref<MapImageCropperExpose | null>(null);
const imageReady = ref(false);
const exportError = ref('');

const form = useForm({
    img: '',
    name: '',
    fileName: '',
});

const submit = async (): Promise<void> => {
    exportError.value = '';
    const image = await cropper.value?.exportImage();

    if (!image) {
        exportError.value = 'Wybierz i przygotuj grafikę mapy przed zapisaniem.';

        return;
    }

    form.img = image.dataUrl;
    form.fileName = image.fileName;
    form.post(route('maps.store'), {
        preserveScroll: true,
    });
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
                    <Message v-if="form.errors.img" severity="error" size="small" variant="simple">{{ form.errors.img }}</Message>
                    <Message v-if="form.errors.fileName" severity="error" size="small" variant="simple">{{ form.errors.fileName }}</Message>
                    <Message v-if="exportError" severity="error" size="small" variant="simple">{{ exportError }}</Message>
                </div>

                <div class="mt-5 flex justify-end">
                    <Button
                        type="button"
                        icon="pi pi-check"
                        label="Utwórz mapę"
                        :disabled="!imageReady || form.processing"
                        :loading="form.processing"
                        @click="submit"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
