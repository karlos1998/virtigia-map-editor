<script setup lang="ts">
import {inject, onMounted, Ref, ref, watch} from "vue";
import {debounce} from "chart.js/helpers";
import axios from "axios";
import {route} from "ziggy-js";

import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {ShopResource} from "../../../Resources/Shop.resource";

// const visible = defineModel<boolean>('visible')

const dialogRef = inject<Ref<DynamicDialogInstance & {

}> | null>('dialogRef');

const selectedShop = ref<ShopResource | null>(null);
const dropdownShops = ref<ShopResource[]>([]);

const searchOptions = debounce((event) => {
    axios.get(route('shops.search', { query: event[0].query }))
        .then(response => {
            dropdownShops.value = response.data;
        });
}, 100);


const save = () => {
    dialogRef.value.close({
        shop: selectedShop.value,
    });
}

const cancel = () => {
    dialogRef.value.close()
}

</script>
<template>

    <div class="font-bold text-lg flex flex-row gap-1 w-full">
        <AutoComplete v-model="selectedShop" :suggestions="dropdownShops" optionLabel="name"
                      placeholder="Szukaj sklepu" class="w-full" @complete="searchOptions"
        >
            <template #option="slotProps">
                <div class="flex align-items-center">
                    <div>{{ slotProps.option.name }}</div>
                </div>
            </template>
        </AutoComplete>
    </div>

    <div class="flex justify-end gap-2">
        <Button type="button" label="Anuluj" severity="secondary" @click="cancel" />
        <Button type="button" label="Zapisz" @click="save" />
    </div>
</template>

<style scoped>

</style>
