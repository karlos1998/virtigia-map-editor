<script setup lang="ts">
import Button from 'primevue/button';
import {Ref, ref, inject} from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {MapResource} from "@/Resources/Map.resource";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import {useForm} from '@inertiajs/vue3';
import {route} from "ziggy-js";
import axios from "axios";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        x: number
        y: number
        map: MapResource
    }
}> | null>('dialogRef');

const form = useForm({
    base_item_id: null,
    respawn_time_seconds: 60,
    map_id: null,
    x: null,
    y: null,
});

const selectedItem = ref<BaseItemResource | null>(null);
const filteredItems = ref<BaseItemResource[]>([]);
const respawnTimeSeconds = ref<number>(60);
const error = ref<string | null>(null);

const search = async (query: string) => {
    const {data} = await axios.get(route('base-items.search', {query}))
    return data;
}

const filterItems = async ({query}: { query: string }) => {
    filteredItems.value = await search(query);
};

const save = () => {
    error.value = null;
    if (!selectedItem.value || !respawnTimeSeconds.value) {
        error.value = 'Wybierz przedmiot i czas odnowienia!';
        return;
    }
    form.base_item_id = selectedItem.value.id;
    form.respawn_time_seconds = respawnTimeSeconds.value;
    form.map_id = dialogRef.value.data.map.id;
    form.x = dialogRef.value.data.x;
    form.y = dialogRef.value.data.y;
    form.post(route('maps.renewable-items.store', {map: dialogRef.value.data.map.id}), {
        onSuccess: () => {
            dialogRef.value.close({item: selectedItem.value});
        },
        onError: (errs) => {
            error.value = errs.base_item_id || errs.respawn_time_seconds || 'Błąd zapisu';
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <AutoComplete
            class="w-full p-0"
            v-model="selectedItem"
            placeholder="Wyszukaj przedmiot"
            :suggestions="filteredItems"
            @complete="filterItems"
            :option-label="(item: BaseItemResource|null) => `${item?.name}`"
            fluid
        >
            <template #option="slotProps">
                <div class="name-item flex items-center space-x-4">
                    <img class="h-12 w-12 object-cover" :src="slotProps.option.src" alt="Option Image"/>
                    <span class="font-semibold text-gray-800">[{{ slotProps.option.id }}] {{
                            slotProps.option.name
                        }}</span>
                </div>
            </template>
        </AutoComplete>
        <div class="mt-2">
            <label>Czas odnowienia (sekundy):</label>
            <input type="number" v-model="respawnTimeSeconds" min="1" max="604800" class="p-2 border rounded w-full"/>
        </div>
        <div class="font-bold my-6">
            <span>{{ dialogRef.data.map.name }}</span>
            <span class="ml-2">({{ dialogRef.data.x }},{{ dialogRef.data.y }})</span>
        </div>
        <div class="text-red-500 font-bold" v-if="error">{{ error }}</div>
        <Button :loading="form.processing" fluid @click="save">Dodaj przedmiot</Button>
    </div>
</template>
