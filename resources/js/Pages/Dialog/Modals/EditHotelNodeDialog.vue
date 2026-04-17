<script setup lang="ts">
import { inject, Ref, ref } from 'vue';
import { debounce } from 'chart.js/helpers';
import axios from 'axios';
import { route } from 'ziggy-js';

import { DynamicDialogInstance } from 'primevue/dynamicdialogoptions';
import { HotelResource } from '@/Resources/Hotel.resource';

const dialogRef = inject<Ref<DynamicDialogInstance> | null>('dialogRef');

const selectedHotel = ref<HotelResource | null>(null);
const dropdownHotels = ref<HotelResource[]>([]);

const searchOptions = debounce((event) => {
    axios.get(route('hotels.search', { query: event[0].query }))
        .then((response) => {
            dropdownHotels.value = response.data;
        });
}, 100);

const save = (): void => {
    dialogRef?.value.close({
        hotel: selectedHotel.value,
    });
};

const cancel = (): void => {
    dialogRef?.value.close();
};
</script>

<template>
    <div class="font-bold text-lg flex flex-row gap-1 w-full">
        <AutoComplete
            v-model="selectedHotel"
            :suggestions="dropdownHotels"
            optionLabel="name"
            placeholder="Szukaj hotelu"
            class="w-full"
            @complete="searchOptions"
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
