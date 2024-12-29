<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import axios from "axios";
import {route} from "ziggy-js";
import {BaseItemResource} from "@/Resources/BaseItem.resource";

const dialogRef = inject<Ref<DynamicDialogInstance> | null>('dialogRef');

const save = () => {
    dialogRef.value.close({
        item: selectedItem.value,
    });
}

const search = async  (query: string) => {
    const { data } = await axios.get(route('base-items.search', {query}))
    console.log(data);
    return data;
}

const selectedItem = ref<BaseItemResource|null>();

const filteredItems = ref<BaseItemResource[]>([]);

const filterItems = async ({ query }: { query: string }) => {
    filteredItems.value = await search(query);
};

</script>

<template>
    <div class="flex flex-col gap-2">

        <AutoComplete
            class="w-full p-0"
            v-model="selectedItem"
            placeholder="Wyszukaj przedmiot"
            :suggestions="filteredItems"
            @complete="filterItems"
            :option-label="(contact: BaseItemResource|null) => `${contact?.name}`"
            fluid
        >
            <template #option="slotProps">
                <div class="name-item flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <img
                            class="h-12 w-12 object-cover"
                            v-tooltip="slotProps.option.src"
                            :src="'https://virtigia-assets.letscode.it/img/' + slotProps.option.src"
                            alt="Option Image"
                        />
                        <div class="text-center">
                            <span class="font-semibold text-gray-800">
                                [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                            </span>
                        </div>
                    </div>
                </div>

            </template>
        </AutoComplete>

        <Button fluid @click="save">Dodaj</Button>
    </div>
</template>

<style scoped>

</style>
