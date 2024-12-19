<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import axios from "axios";
import {route} from "ziggy-js";
import {BaseItemResource} from "@/Resources/BaseItem.resource";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        label: string,
        content: string,
    }
}> | null>('dialogRef');

const label = ref('');
const content = ref('');
onMounted(() => {
    label.value = dialogRef.value.data?.label ?? '';
    content.value = dialogRef.value.data?.content ?? '';
})

const save = () => {
    dialogRef.value.close({
        label: label.value,
        content: content.value,
    });
}

const search = async  (query: string) => {
    const { data } = await axios.get(route('base-items.search', {query}))
    console.log(data);
    return data;
}

const selectedItem = ref();

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
        >
            <template #option="slotProps">
                <div class="name-item flex align-items-center justify-content-between">
                    <div class="flex align-items-center">
                        <div class="flex justify-content-between">
                            <div class="flex-column align-items-center">
                                [{{slotProps.option.id }}] {{slotProps.option.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </AutoComplete>

        <Button fluid @click="save">Save</Button>
    </div>
</template>

<style scoped>

</style>
