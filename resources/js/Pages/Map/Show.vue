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
import EditOption from "@/Pages/Dialog/Modals/EditOption.vue";
import {DynamicDialogCloseOptions, DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {useDialog} from "primevue/usedialog";
import AddNpcToMap from "@/Pages/Map/Modals/AddNpcToMap.vue";

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


const primeDialog = useDialog();
const addNpcToMapDialogInstance = ref<DynamicDialogInstance>();
const lastSelectedNpc = ref<NpcResource>();


const toggleCollision = (x: number, y: number) => {
    const index = y * props.map.x + x;
    const colArray = props.map.col.split('');
    colArray[index] = colArray[index] === '0' ? '1' : '0';
    props.map.col = colArray.join('');
};

const addNewObject = (event: MouseEvent) => {
    console.log('addNewObject', event, trackerPosition.value);


    if(editColsOn.value) {
        toggleCollision(trackerPosition.value.x, trackerPosition.value.y)
        return;
    }

    if(addNpcToMapDialogInstance.value) {
        console.log(addNpcToMapDialogInstance.value);
    }

    addNpcToMapDialogInstance.value = primeDialog.open(AddNpcToMap, {
        props: {
            header: 'Dodawanie NPC do mapy'
        },
        data: {
            x: trackerPosition.value.x,
            y: trackerPosition.value.y,
            map: props.map,
            lastSelectedNpc: lastSelectedNpc.value,
        },
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { npc?: NpcResource } }) {
            if(closeOptions.data && closeOptions.data.npc) {
                lastSelectedNpc.value = closeOptions.data.npc;
            }
        }
    });
};

const throughTheDoor = (door: DoorResource) => {
    router.get(route('maps.show', door.go_map_id));
}

const removeNpc = (npc: NpcResource) => {
    console.log('test', npc)
    router.delete(route('npcs.destroy', npc.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirm.close();
        }
    });
}



const isPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });
const mapOffset = ref({ x: 0, y: 0 });

const handleMouseMove = (event: MouseEvent) => {
    if (isPanning.value) {
        mapOffset.value = {
            x: event.clientX - panStart.value.x,
            y: event.clientY - panStart.value.y,
        };
    } else {
        setTrackerPosition(event);
    }
};

const startPanning = (event: MouseEvent) => {
    if (event.button === 2) { // Sprawdza, czy prawy przycisk myszy jest wciśnięty
        isPanning.value = true;
        panStart.value = { x: event.clientX - mapOffset.value.x, y: event.clientY - mapOffset.value.y };
        event.preventDefault();
    }
};

const panMap = (event: MouseEvent) => {
    if (isPanning.value) {
        mapOffset.value = {
            x: event.clientX - panStart.value.x,
            y: event.clientY - panStart.value.y,
        };
    }
};

const stopPanning = () => {
    isPanning.value = false;
};




const collisionPositions = computed(() => {
    const positions = [];
    const colArray = props.map.col.split('');
    colArray.forEach((val, index) => {
        if (val === '1') {
            const x = (index % props.map.x) * 32;
            const y = Math.floor(index / props.map.x) * 32;
            positions.push({ x, y });
        }
    });
    return positions;
});

const editColsOn = ref(false)

const saveCols = () => {
    router.patch(route('maps.update.col', {
        map: props.map.id,
    }), {
        col: props.map.col
    });
}

const npcScale = ref(true);

const isMapVisible = ref(true);
const forceUpdate = () => {
    //i tak nie dziala :X
    isMapVisible.value = false;
    setTimeout(() => isMapVisible.value = true, 100)
}
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

                    <Link
                        :href="route('npcs.show', message.npc.id)"
                    >
                        <Button label="Pokaż szczegóły" @click="rejectCallback" size="small" />
                    </Link>

                    <Button label="Usuń" @click="removeNpc(message.npc)" severity="danger" size="small" />
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

        <Message class="my-6">
            Protip: Klikając na przejścia (czarne kwadraciki) przeniesie nas na konkretną mapkę bez potrzeby jej wyszukiwania.
        </Message>


        <div class="coordinates-card">
            <p>X: {{trackerPosition.x}}, Y: {{trackerPosition.y}}</p>
        </div>

        <div class="card">
            <div class="flex gap-2">
                <Button label="+" icon="pi pi-search-plus" @click="zoomIn" />
                <Button label="-" icon="pi pi-search-minus" @click="zoomOut" />
            </div>

            <div class="flex gap-2 justify-end items-center">
                <label for="editColsCheckbox">Edytuj kolizje</label>
                <Checkbox id="editColsCheckbox" v-model="editColsOn" binary />
                <Button v-if="editColsOn" @click="saveCols" label="Zapisz kolizje" />
            </div>

            <!-- I tak nie dziala -->
<!--            <div class="mt-4 flex gap-2 justify-end items-center">-->
<!--                <label for="npcScale">Skalowanie NPC</label>-->
<!--                {{npcScale}}-->
<!--                <Checkbox id="npcScale" @change="forceUpdate" v-model="npcScale" binary />-->
<!--            </div>-->
        </div>


        <div class="card overflow-auto m-2" v-if="isMapVisible">
            <div
                class="map-container relative"
                :style="{
        backgroundImage: `url(https://s3.letscode.it/virtigia-assets/img/locations/${map.src})`,
        width: `${map.x * 32 * scale}px`,
        height: `${map.y * 32 * scale}px`,
        transformOrigin: 'top left',
        transform: `translate(${mapOffset.x}px, ${mapOffset.y}px)`,
    }"
                @mousemove="handleMouseMove"
                @mousedown="startPanning"
                @mouseup="stopPanning"
                @mouseleave="stopPanning"
                @contextmenu.prevent
                @click.self="addNewObject"
            >
                <div
                    class="mouse-tracker absolute bg-yellow-500/70 pointer-events-none" :style="{
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    top: trackerPosition.y * 32 * scale,
                    left: trackerPosition.x * 32 * scale,
                }" />

                <div
                    v-for="npc in npcs"
                    :key="`npc-${npc.id}-${npcScale}`"
                    class="absolute npc"
                    v-tooltip="npc.name"
                    @click="showNpcConfirmDialog($event, npc)"
                    :style="{
                        top: `${(npc.location.y * 32 - ((npcHeights[npc.id] ?? 32) - 32)) * scale}px`,
                        left: `${npc.location.x * 32 * scale}px`,
                        width: npcScale ? `${(npcWidths[npc.id] ?? 32) * scale}px` : undefined,
                        height: npcScale ? `${(npcHeights[npc.id] ?? 32) * scale}px` : undefined,
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
                        :src="`https://s3.letscode.it/virtigia-assets/img/npc/${npc.src}`"
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

                <div
                    v-for="(pos, index) in collisionPositions"
                    :key="index"
                    class="col"
                    :class="{
                        'col-opacity': !editColsOn
                    }"
                    :style="{
                        top: `${pos.y * scale}px`,
                        left: `${pos.x * scale}px`,
                        width: `${32 * scale}px`,
                        height: `${32 * scale}px`
                    }"
                    @click.stop
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

.col {
    width: 32px;
    height: 32px;
    position:absolute;
    background-color: #ba5208;
    pointer-events: none;
}

.col-opacity {
    opacity: 0.7;
}

.double-sided {
    background-color: #000000 !important;
    /* border: 6px black solid; */
}


.coordinates-card {
    position: fixed;
    bottom: 10px;
    right: 10px;
    z-index: 1000;
    width: 150px;
    text-align: center;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #ddd;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
</style>
