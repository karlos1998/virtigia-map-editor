<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { MapResource } from '@/Resources/Map.resource';

const props = defineProps<{
    map: MapResource;
    scale: number;
}>();

const emit = defineEmits<{
    (e: 'zoomIn'): void;
    (e: 'zoomOut'): void;
    (e: 'editColsChanged', value: boolean): void;
}>();

const editColsOn = ref(false);

const toggleEditCols = () => {
    editColsOn.value = !editColsOn.value;
    emit('editColsChanged', editColsOn.value);
};

const saveCols = () => {
    router.patch(route('maps.update.col', {
        map: props.map.id,
    }), {
        col: props.map.col
    });
};
</script>

<template>
    <div class="card">
        <div class="flex gap-2">
            <Button label="+" icon="pi pi-search-plus" @click="emit('zoomIn')" />
            <Button label="-" icon="pi pi-search-minus" @click="emit('zoomOut')" />
        </div>

        <div class="flex gap-2 justify-end items-center">
            <label for="editColsCheckbox">Edytuj kolizje</label>
            <Checkbox id="editColsCheckbox" v-model="editColsOn" binary @change="toggleEditCols" />
            <Button v-if="editColsOn" @click="saveCols" label="Zapisz kolizje" />
        </div>
    </div>
</template>
