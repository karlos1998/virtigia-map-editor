<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { MapResource } from "@/Resources/Map.resource";
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { NpcResource } from "@/Resources/Npc.resource";

defineProps<{
    map: MapResource;
    npcs: NpcResource[];
}>();

const scale = ref(1);

const zoomIn = () => {
    scale.value = Math.min(scale.value + 0.1, 2);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value - 0.1, 0.5);
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <Link :href="route('maps.index')">
                <Button label="Powrót" severity="info" />
            </Link>
        </div>

        <div class="card">
            <div class="flex gap-2">
                <Button label="+" icon="pi pi-search-plus" @click="zoomIn" />
                <Button label="-" icon="pi pi-search-minus" @click="zoomOut" />
            </div>
        </div>
        <div class="card overflow-auto m-2">
            <div
                class="map-container relative"
                :style="{
                    backgroundImage: `url(https://virtigia-assets.letscode.it/img/locations/${map.src})`,
                    width: `${map.x * 32 * scale}px`,
                    height: `${map.y * 32 * scale}px`,
                    transformOrigin: 'top left',
                }"
            >
                <div
                    v-for="npc in npcs"
                    :key="npc.id"
                    class="absolute npc"
                    :style="{
                        'min-width': `${32 * scale}px`,
                        'min-height': `${32 * scale}px`,
                        top: `${npc.y * 32 * scale}px`,
                        left: `${npc.x * 32 * scale}px`,
                    }"
                >
                    <img :src="`https://virtigia-assets.letscode.it/img/npc/${npc.src}`" :style="{ transform: `scale(${scale})`, transformOrigin: 'top left' }" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.map-container {
    background-size: contain;
    background-repeat: no-repeat;
    background-position: top left;
    position: relative;
}

.npc {
    background-color: red;
    position: absolute;
}
</style>
