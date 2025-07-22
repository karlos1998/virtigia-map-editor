<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { MapResource } from '@/Resources/Map.resource';
import { useConfirm } from 'primevue';

const props = defineProps<{
    map: MapResource;
    scale: number;
}>();

const confirm = useConfirm();

const emit = defineEmits<{
    (e: 'zoomIn'): void;
    (e: 'zoomOut'): void;
    (e: 'editColsChanged', value: boolean): void;
    (e: 'editWaterChanged', value: boolean): void;
}>();

// Use a single editMode ref instead of separate booleans
const editMode = ref('none'); // 'none', 'cols', or 'water'

// Watch for changes to editMode and emit events
watch(editMode, (newMode, oldMode) => {
    // Update collision editing state
    const colsOn = newMode === 'cols';
    if (colsOn !== (oldMode === 'cols')) {
        emit('editColsChanged', colsOn);
    }

    // Update water editing state
    const waterOn = newMode === 'water';
    if (waterOn !== (oldMode === 'water')) {
        emit('editWaterChanged', waterOn);
    }
});

const saveCols = () => {
    router.patch(route('maps.update.col', {
        map: props.map.id,
    }), {
        col: props.map.col
    });
};

const saveWater = () => {
    router.patch(route('maps.update.water', {
        map: props.map.id,
    }), {
        water: props.map.water
    });
};

const confirmClearCollisions = (event) => {
    confirm.require({
        target: event.currentTarget,
        message: 'Czy na pewno chcesz wyczyścić kolizje? Ta operacja jest nieodwracalna.',
        icon: 'pi pi-info-circle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Wyczyść',
            severity: 'danger'
        },
        accept: () => {
            router.patch(route('maps.clear.collisions', {
                map: props.map.id,
            }));
        },
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
            <label>Tryb edycji:</label>
            <div class="p-field-radiobutton">
                <RadioButton id="editModeNone" name="editMode" value="none" v-model="editMode" />
                <label for="editModeNone" class="ml-1 mr-3">Brak</label>
            </div>
            <div class="p-field-radiobutton">
                <RadioButton id="editModeCols" name="editMode" value="cols" v-model="editMode" />
                <label for="editModeCols" class="ml-1 mr-3">Kolizje</label>
            </div>
            <div class="p-field-radiobutton">
                <RadioButton id="editModeWater" name="editMode" value="water" v-model="editMode" />
                <label for="editModeWater" class="ml-1">Woda</label>
            </div>
            <Button v-if="editMode === 'cols'" @click="saveCols" label="Zapisz kolizje" class="ml-3" />
            <Button v-if="editMode === 'water'" @click="saveWater" label="Zapisz wodę" class="ml-3" />
        </div>

        <Button @click="confirmClearCollisions" label="Wyczyść kolizje" severity="danger" class="ml-3" />
    </div>
</template>
