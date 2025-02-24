<script setup lang="ts">
import {inject, onMounted, Ref, ref, watch} from "vue";
import {debounce} from "chart.js/helpers";
import axios from "axios";
import {route} from "ziggy-js";
import {DialogNodeTeleportationDataResource} from "../Resources/DialogNodeTeleportationData.resource";
import {MapResource} from "../Resources/Map.resource";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";

// const visible = defineModel<boolean>('visible')

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        option: {
            label: string,
            id: string
        },
        parent: string
    }
}> | null>('dialogRef');


const emit = defineEmits(['selected']);

onMounted(() => {
    teleportation.value = dialogRef.value.data?.teleportation;
})

const scale = ref(1);

const teleportation = ref(null);

const selectedMap = ref<MapResource | null>(null);
const dropdownMaps = ref<MapResource[]>([]);

const searchOptions = debounce((event) => {
    axios.get(route('maps.search', { search: event[0].query }))
        .then(response => {
            dropdownMaps.value = response.data;
        });
}, 100);

const handleMouseMove = (event: MouseEvent) => {
    setTrackerPosition(event);
};

const trackerPosition = ref({ x: 0, y: 0 });
const setTrackerPosition = (event: MouseEvent) => {
    if (event.target !== event.currentTarget) {
        trackerPosition.value = { x: -32, y: -32 };
        return;
    }
    trackerPosition.value = {
        x: (event.offsetX / scale.value / 32) | 0,
        y: (event.offsetY / scale.value / 32) | 0
    };
};

const handleClick = (event: MouseEvent) => {
    // const position = {
    //     x: trackerPosition.value.x,
    //     y: trackerPosition.value.y,
    // }
    // emit('selected', position);
    teleportation.value = {
        x: trackerPosition.value.x,
        y: trackerPosition.value.y,
        mapId: selectedMap.value.id,
        mapName: selectedMap.value.name,
    }
    changed.value = true;
}

const changed = ref(false);

const reset = () => {
    teleportation.value = dialogRef.value.data?.teleportation;
    changed.value = false;
}

const save = () => {
    dialogRef.value.close({
        teleportation: teleportation.value,
    });
}

const cancel = () => {
    dialogRef.value.close()
}

</script>
<template>
    <span v-if="teleportation" class="text-surface-500 dark:text-surface-400 block mb-8">Aktualna teleportacja: [{{teleportation.mapId}}] {{teleportation.mapName}} ({{teleportation.x}}, {{teleportation.y}})</span>
    <span v-else class="text-surface-500 dark:text-surface-400 block mb-8">Brak ustawionej teleportacji</span>

    <div v-if="changed" class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="cancel" />
            <Button type="button" label="Koryguj" severity="info" @click="reset" />
            <Button type="button" label="Zapisz" @click="save" />
    </div>
    <div v-else>
        <div class="font-bold text-lg flex flex-row gap-1 w-full">
            <AutoComplete v-model="selectedMap" :suggestions="dropdownMaps" optionLabel="name"
                      placeholder="Szukaj lokacji" class="w-full" @complete="searchOptions"
            >
                <template #option="slotProps">
                    <div class="flex align-items-center">
                        <div>{{ slotProps.option.name }}</div>
                    </div>
                </template>
            </AutoComplete>
        </div>
        <div
            v-if="selectedMap && selectedMap.id > 0"

        >
            <div
                class="map-container relative"
                @mousemove="handleMouseMove"
                :style="{
                    backgroundImage: `url(${selectedMap.src})`,
                    width: `${selectedMap.x * 32 * scale}px`,
                    height: `${selectedMap.y * 32 * scale}px`,
                    transformOrigin: 'top left',
                    transform: `translate(${selectedMap.x}px, ${selectedMap.y}px)`,
                }"
                @click.self="handleClick"
            >
                <div
                    class="mouse-tracker absolute bg-yellow-500/70 pointer-events-none" :style="{
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    top: trackerPosition.y * 32 * scale,
                    left: trackerPosition.x * 32 * scale,
                }" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.map-container {
    
}
</style>
