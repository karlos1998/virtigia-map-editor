<script setup lang="ts">
import {ref} from "vue";
import {debounce} from "chart.js/helpers";
import axios from "axios";
import {route} from "ziggy-js";
import {DialogNodeTeleportationDataResource} from "../Resources/DialogNodeTeleportationData.resource";
import {MapResource} from "../Resources/Map.resource";

const visible = defineModel<boolean>('visible')

defineProps<DialogNodeTeleportationDataResource>()

const selectedMap = ref<MapResource | null>(null);
const dropdownMaps = ref<MapResource[]>([]);

const searchOptions = debounce((event) => {
    axios.get(route('maps.search', { search: event.query }))
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

const scale = ref(1);
</script>
<template>
    <Dialog v-model:visible="visible" modal header="Edycja miejsca teleportacji" :style="{ 'min-width': '25rem' }" class="mx-5">
        <span class="text-surface-500 dark:text-surface-400 block mb-8">Aktualna teleportacja: [{{teleportation.mapId}}] {{teleportation.mapName}} ({{teleportation.x}}, {{teleportation.y}})</span>
        <div class="font-bold text-lg flex flex-row gap-1 w-full">
            <Dropdown v-model="selectedMap" :options="dropdownMaps" filter optionLabel="name"
                      placeholder="Szukaj lokacji" class="w-full" @filter="searchOptions"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex align-items-center">
                        <div>{{ slotProps.value.name }}</div>
                    </div>
                    <span v-else>
                        {{ slotProps.placeholder }}
                    </span>
                </template>

                <template #option="slotProps">
                    <div class="flex align-items-center">
                        <div>{{ slotProps.option.name }}</div>
                    </div>
                </template>
            </Dropdown>
        </div>
        <div
            v-if="selectedMap"

        >
            <div
                class="map-container relative"
                @mousemove="handleMouseMove"
                :style="{
                backgroundImage: `url(https://s3.letscode.it/virtigia-assets/img/locations/${selectedMap.src})`,
                width: `${selectedMap.x * 32 * scale}px`,
                height: `${selectedMap.y * 32 * scale}px`,
                transformOrigin: 'top left',
                transform: `translate(${selectedMap.x}px, ${selectedMap.y}px)`,
    }"
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
        <div class="flex justify-end gap-2">
<!--            <Button type="button" label="Cancel" severity="secondary" @click="visible = false"></Button>-->
<!--            <Button type="button" label="Save" @click="visible = false"></Button>-->
        </div>
    </Dialog>
</template>

<style scoped>

</style>
