<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapResource } from '@/Resources/Map.resource';
import {computed, ref} from 'vue';
import {Link, router, useForm} from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import {NpcResource, NpcWithLocationResource} from '@/Resources/Npc.resource';
import { DoorResource } from '@/Resources/Door.resource';
import { useConfirm } from 'primevue';
import ItemHeader from "@/Components/ItemHeader.vue";
import EditOption from "@/Pages/Dialog/Modals/EditOption.vue";
import {DynamicDialogCloseOptions, DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {useDialog} from "primevue/usedialog";
import AddNpcToMap from "@/Pages/Map/Modals/AddNpcToMap.vue";

const src = ref(null);

const form = useForm({
    img: new Image(),
    name: '',
    fileName: '',
})

function onFileSelect(event) {
    const file = event.files[0];
    const reader = new FileReader();

    form.fileName = file.name

    reader.onload = async (e) => {
        src.value = e.target.result;

        const img = new Image();
        img.onload = () => {
            form.img = img;
        };
        img.src = e.target.result as string;
    };

    reader.readAsDataURL(file);
}

const submit = () => {
    form
        .transform(({name, img, fileName}) => {
            console.log(img);
            return {
                name,
                img: img.src,
                fileName,
            }
        })
        .post(route('maps.store'));
}
</script>

<template>
    <AppLayout>


        <ItemHeader
            :route-back="route('maps.index')"
        >
            <template #header>
                Tworzenie nowej mapy
            </template>
        </ItemHeader>


        <div class="card">
            <FileUpload mode="basic" @select="onFileSelect" customUpload auto severity="secondary" class="p-button-outlined" />
            <img v-if="src" :src="src" alt="Image" class="rounded-xl w-full sm:w-64" />
            <Message severity="error" v-if="form.img.width % 32 || form.img.height % 32">Błędna grafika! Mapa musi mieć wymiary podzielne przez 32</Message>

            <InputText type="text" v-model="form.name" placeholder="Nazwa Mapy" />
            <Message v-if="form.errors.name" severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
            <Message v-if="form.errors.img" severity="error" size="small" variant="simple">{{ form.errors.img }}</Message>
            <Message v-if="form.errors.fileName" severity="error" size="small" variant="simple">{{ form.errors.fileName }}</Message>

            <Button type="submit" severity="secondary" label="Utwórz mape" @click="submit" :loading="form.processing" />
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
