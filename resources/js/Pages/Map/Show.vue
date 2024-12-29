<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapResource } from '@/Resources/Map.resource';
import {computed, ref} from 'vue';
import {Link, router} from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import {NpcResource, NpcWithLocationResource} from '@/Resources/Npc.resource';
import { DoorResource } from '@/Resources/Door.resource';
import { useConfirm } from 'primevue';
import ItemHeader from "@/Components/ItemHeader.vue";

const props = defineProps<{
    map: MapResource;
    npcs: NpcWithLocationResource[];
    doors: DoorResource[];
}>();

const scale = ref(1);

const zoomIn = () => {
    scale.value = Math.min(scale.value + 0.1, 2);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value - 0.1, 0.5);
};


const npcWidths = ref<Record<string, number>>({});
const npcHeights = ref<Record<string, number>>({});
const adjustNpcOffset = (id: string, element: HTMLImageElement) => {
    npcWidths.value[id] = element.width;
    npcHeights.value[id] = element.height;
};

const confirm = useConfirm();

const showNpcConfirmDialog = (event: MouseEvent, npc: NpcResource) => {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        group: 'npc',
        message: 'Please confirm to proceed moving forward.',
        icon: 'pi pi-exclamation-circle',
        npc,
        rejectProps: {
            icon: 'pi pi-times',
            label: 'Cancel',
            outlined: true
        },
        acceptProps: {
            icon: 'pi pi-check',
            label: 'Confirm'
        },
        accept: () => {
            // toast.add({severity:'info', summary:'Confirmed', detail:'You have accepted', life: 3000});
        },
        reject: () => {
            // toast.add({severity:'error', summary:'Rejected', detail:'You have rejected', life: 3000});
        }
    });
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

const addNewObject = (event: MouseEvent) => {
    console.log('addNewObject', event, trackerPosition.value);
};

const throughTheDoor = (door: DoorResource) => {
    router.get(route('maps.show', door.go_map_id));
}

interface NpcWithLocation {
    id: number
    name: string
    src: string
    x: number
    y: number
}

// const allNpcs = computed<NpcWithLocation[]>(() => {
//     return props.npcs.flatMap(npc =>
//         npc.locations.map(location => ({
//             id: npc.id,
//             name: npc.name,
//             src: npc.src,
//             x: location.x,
//             y: location.y,
//         }))
//     )
// })
</script>

<template>
    <AppLayout>

        <ConfirmPopup group="npc">
            <template #container="{ message, acceptCallback, rejectCallback }">
                <div
                    class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700 p-4 mb-4 pb-0">
                    <!--                    <i :class="slotProps.message.icon" class="text-6xl text-primary-500"></i>-->
                    <p>{{ message.npc.name }}</p>
                </div>

                <div class="flex justify-center items-center gap-2 mt-4">
                    <!--                    <Button label="Save" @click="acceptCallback" size="small"></Button>-->
                    <Button label="Zamknij" severity="contrast" @click="rejectCallback" size="small" />
                    <Button label="Kopiuj" severity="help" @click="rejectCallback" size="small" />
                    <Button label="Pokaż szczegóły" @click="rejectCallback" size="small" />
                    <Button label="Usuń" @click="rejectCallback" severity="danger" size="small" />
                </div>

            </template>

        </ConfirmPopup>


        <ItemHeader
            :route-back="route('maps.index')"
        >
            <template #header>
                #{{ map.id }} - {{ map.name }}
            </template>
        </ItemHeader>


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
                @mousemove.stop="setTrackerPosition($event)"
                @click.self="addNewObject"
            >
                <div class="mouse-tracker absolute bg-yellow-500/70 pointer-events-none" :style="{
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    top: trackerPosition.y * 32 * scale,
                    left: trackerPosition.x * 32 * scale,
                }" />

                <div
                    v-for="npc in npcs"
                    :key="npc.id"
                    class="absolute npc"
                    v-tooltip="npc.name"
                    @click="showNpcConfirmDialog($event, npc)"
                    :style="{
                        width: `${(npcWidths[npc.id] ?? 32) * scale}px`,
                        height: `${(npcHeights[npc.id] ?? 32) * scale}px`,
                        top: `${(npc.location.y * 32 - ((npcHeights[npc.id] ?? 32) - 32)) * scale}px`,
                        left: `${npc.location.x * 32 * scale}px`,
                    }"
                >
                    <!-- Czerwony kwadrat u podstawy -->
                    <div
                        class="npc-footer"
                        :style="{
                            width: `${32 * scale}px`,
                            height: `${32 * scale}px`,
                            bottom: 0,
                        }"
                    />
                    <!-- Obrazek NPC -->
                    <img
                        :src="`https://virtigia-assets.letscode.it/img/npc/${npc.src}`"
                        :style="{
                            position: 'relative',
                            width: `${npcWidths[npc.id] * scale}px`,
                            bottom: 0,
                            zIndex: 1,
                            left: (32 - npcWidths[npc.id]) * scale / 2,
                        }"
                        @load="adjustNpcOffset(npc.id, $event.target as HTMLImageElement)"
                        alt="npc" />
                </div>


                <div
                    class="door"
                    v-for="door in doors"
                    v-tooltip="'Przejście do: ' + door.name + ' (' + door.go_x + ',' + door.go_y + '), \nPowrót: ' + (door.double_sided ? 'Tak' : 'Nie' )"
                    @click="throughTheDoor(door)"
                    :style="{
                        width: `${32 * scale}px`,
                        height: `${32 * scale}px`,
                        top: `${door.y * 32 * scale}px`,
                        left: `${door.x * 32 * scale}px`,
                    }"
                    :class="{'double-sided': door.double_sided}"
                />

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
    position: absolute;
}

.npc-footer {
    position: absolute;
    background-color: red;
}


.door {
    width: 32px;
    height: 32px;
    position:absolute;
    background-color: #353030;
}

.double-sided {
    background-color: #000000 !important;
    /* border: 6px black solid; */
}
</style>
