<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapResource } from '@/Resources/Map.resource';
import {computed, ref} from 'vue';
import {Link, router} from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import {NpcResource, NpcWithLocationResource} from '@/Resources/Npc.resource';
import { DoorResource } from '@/Resources/Door.resource';
import {useConfirm, useToast} from 'primevue';
import ItemHeader from "@/Components/ItemHeader.vue";
import EditOption from "@/Pages/Dialog/Modals/EditOption.vue";
import {DynamicDialogCloseOptions, DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {useDialog} from "primevue/usedialog";
import AddNpcToMap from "@/Pages/Map/Modals/AddNpcToMap.vue";
import TeleportationSelectModal from "../../Components/TeleportationSelectModal.vue";
import {DialogNodeTeleportationDataResource} from "../../Resources/DialogNodeTeleportationData.resource";
import axios from "axios";

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
        npc,
    });
};

const showDoorConfirmDialog = (event: MouseEvent, door: DoorResource) => {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        group: 'door',
        door,
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
    console.log('toggle collision')
    const index = y * props.map.x + x;
    const colArray = props.map.col.split('');
    colArray[index] = colArray[index] === '0' ? '1' : '0';
    props.map.col = colArray.join('');
};

const addNewObject = (event: MouseEvent) => {

    console.log('addNewObject')

    const x = trackerPosition.value.x;
    const y = trackerPosition.value.y;

    if(moveNpcLocationData.value) {
        updateMoveNpcLocation(x, y)
        return;
    }

    if(moveDoorLocationData.value) {
        updateMoveDoorLocation(x, y)
        return;
    }

    if(editColsOn.value) {
        toggleCollision(x, y)
        return;
    }

    if(addNpcToMapDialogInstance.value) {
        console.log(addNpcToMapDialogInstance.value);
    }

    addNpcToMapDialogInstance.value = primeDialog.open(AddNpcToMap, {
        props: {
            header: 'Dodawanie NPC do mapy',
            modal: true,
        },
        data: {
            x,
            y,
            map: props.map,
            lastSelectedNpc: lastSelectedNpc.value,
        },
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { npc?: NpcResource } }) {
            if(closeOptions.data && closeOptions.data.npc) {
                lastSelectedNpc.value = closeOptions.data.npc;
            } else if(closeOptions.data?.addDoor ) {
                addDoorTo(x, y);
            }
        }
    });
};

const toast = useToast()
const addDoorTo = (x: number, y: number) => {

    primeDialog.open(TeleportationSelectModal, {
        props: {
            header: 'Edycja miejsca teleportacji',
            modal: true,
            breakpoints:{
                '960px': '75vw',
                '640px': '90vw'
            },
            style: 'max-width:90%'
        },
        data: {

        },
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { teleportation: DialogNodeTeleportationDataResource } }) {
            if(closeOptions.data?.teleportation) {
                console.log('new teleportation data', closeOptions.data.teleportation)
                router.post(route('doors.store'), {
                    map_id: props.map.id,
                    x,
                    y,
                    go_map_id: closeOptions.data.teleportation.mapId,
                    go_x:  closeOptions.data.teleportation.x,
                    go_y:  closeOptions.data.teleportation.y,
                }, {
                    onSuccess: () => {
                        toast.add({ severity: 'success', summary: 'Udało się', detail: 'Utworzono nowe przejście', life: 4000 });

                        confirm.require({
                            message: 'Czy chcesz na docelowej mapie również umieścić przejście powrotne?',
                            header: 'Przejscie powrotne',
                            icon: 'pi pi-info-circle',
                            rejectProps: {
                                label: 'Odrzuć',
                                severity: 'secondary',
                                outlined: true
                            },
                            acceptProps: {
                                label: 'Potwierdzam',
                            },
                            accept: () => {
                                router.post(route('doors.store'), {
                                    map_id: closeOptions.data.teleportation.mapId,
                                    x:  closeOptions.data.teleportation.x,
                                    y:  closeOptions.data.teleportation.y,

                                    go_map_id: props.map.id,
                                    go_x: x,
                                    go_y: y,
                                }, {
                                    onSuccess: () => {
                                        toast.add({ severity: 'success', summary: 'Udało się', detail: 'Utworzono przejście powrotne', life: 4000 });

                                    },
                                    onError: () => {
                                        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się umieścić przejścia powrotnego', life: 6000 });
                                    }
                                })
                            }
                        });

                    },
                    onError: () => {
                        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się umieścić przejścia', life: 6000 });
                    }
                })
            }
        }
    });
}

const throughTheDoor = (door: DoorResource) => {
    router.get(route('maps.show', door.go_map_id));
}

const removeNpc = (npc: NpcWithLocationResource) => {
    console.log('test', npc)
    router.delete(route('npcs.locations.destroy', {
        npc: npc.id,
        npcLocation: npc.location.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            confirm.close();
        }
    });
}

const removeFromGroup = (npc: NpcWithLocationResource) => {
    router.delete(route('npcs.group.detach', {
        npc: npc.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            // ...
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

const removeDoor = (door: DoorResource) => {
    router.delete(route('doors.destroy', {
        door
    }))
}


const mouseTrackerEl = ref<HTMLElement | null>(null)




const moveDoorLocationData = ref<DoorResource>(null);

const moveDoor = (door: DoorResource) => {
    moveDoorLocationData.value = door;
    moveNpcLocationData.value = null;
}

const updateMoveDoorLocation = (x: number, y: number) => {
    router.patch(route('doors.move', {
        door: moveDoorLocationData.value.id,
    }), {
        x,
        y,
    }, {
        preserveScroll: true,
        preserveState: true,
    })
    moveDoorLocationData.value = null;
}




const moveNpcLocationData = ref<NpcWithLocationResource>(null);
const moveNpc = (npc: NpcWithLocationResource) => {
    moveDoorLocationData.value = null;
    moveNpcLocationData.value = npc;
}


const updateMoveNpcLocation = (x: number, y: number) => {
    router.patch(route('npcs.update.location', {
        npc: moveNpcLocationData.value.id,
        npcLocation: moveNpcLocationData.value.location.id,
    }), {
        map_id: moveNpcLocationData.value.location.map_id,
        x,
        y,
    }, {
        preserveScroll: true,
        preserveState: true,
    })
    moveNpcLocationData.value = null;
}

const groupColors = [
    '#00FFFF', '#FFFF00', '#FF00FF', '#FFD700', '#ADFF2F',
    '#87CEFA', '#FF69B4', '#BA55D3', '#7FFFD4', '#FFA07A',
    '#E0FFFF', '#F0E68C', '#D8BFD8', '#FFE4B5', '#B0E0E6',
    '#FFB6C1', '#AFEEEE', '#F5DEB3', '#DDA0DD', '#F4A460'
]
const getGroupColor = (groupId) => groupColors[groupId % groupColors.length]



const isPaintingCollision = ref(false)
const paintingMode = ref<'add' | 'remove' | null>(null)

const paintCollision = (event: MouseEvent) => {
    if (!editColsOn.value || !isPaintingCollision.value || !paintingMode.value) return

    const x = Math.floor(event.offsetX / scale.value / 32)
    const y = Math.floor(event.offsetY / scale.value / 32)
    const index = y * props.map.x + x
    const colArray = props.map.col.split('')

    if (paintingMode.value === 'add' && colArray[index] === '0') {
        colArray[index] = '1'
        props.map.col = colArray.join('')
    } else if (paintingMode.value === 'remove' && colArray[index] === '1') {
        colArray[index] = '0'
        props.map.col = colArray.join('')
    }
}

const startPaintingCollision = (event: MouseEvent) => {
    if (!editColsOn.value || event.button !== 0) return

    paintingStart.value = {
        x: event.clientX,
        y: event.clientY,
    }

    // nie uruchamiamy jeszcze paintingMode ani isPaintingCollision – dopiero w `mousemove`
}


const stopPaintingCollision = () => {
    isPaintingCollision.value = false
    paintingMode.value = null
    paintingStart.value = null
}

const paintingStart = ref<{ x: number, y: number } | null>(null)



</script>

<template>
    <AppLayout>

        <ConfirmDialog />

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

                    <Button label="Wyklucz z grupy" severity="help" @click="removeFromGroup(message.npc); rejectCallback()" size="small" />

                    <Link
                        :href="route('npcs.show', message.npc.id)"
                    >
                        <Button label="Pokaż szczegóły" @click="rejectCallback" size="small" />
                    </Link>

                    <Button label="Usuń" @click="removeNpc(message.npc)" severity="danger" size="small" />

                    <Button label="Przenieś" @click="moveNpc(message.npc); rejectCallback()" severity="warn" size="small" />


                </div>

            </template>

        </ConfirmPopup>

        <ConfirmPopup group="door">
            <template #container="{ message, acceptCallback, rejectCallback }">
                <div
                    class="flex flex-col items-center w-full gap-4 border-b border-surface-200 dark:border-surface-700 p-4 mb-4 pb-0">
                    <!--                    <i :class="slotProps.message.icon" class="text-6xl text-primary-500"></i>-->
                    <p>{{ message.door.name }}</p>
                </div>

                <div class="flex justify-center items-center gap-2 mt-4">
                    <Button label="Zamknij" severity="contrast" @click="rejectCallback" size="small" />

                    <Link
                        :href="route('maps.show', message.door.go_map_id)"
                    >
                        <Button label="Przejdź" @click="rejectCallback" size="small" />
                    </Link>

                    <Button label="Usuń" @click="removeDoor(message.door); rejectCallback()" severity="danger" size="small" />

                    <Button label="Przesuń" @click="moveDoor(message.door); rejectCallback()" severity="info" size="small" />
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
        backgroundImage: `url(${map.src})`,
        width: `${map.x * 32 * scale}px`,
        height: `${map.y * 32 * scale}px`,
        transformOrigin: 'top left',
        transform: `translate(${mapOffset.x}px, ${mapOffset.y}px)`,
    }"
                @mousemove="(e) => {
                    if (paintingStart) {
                        const dx = Math.abs(e.clientX - paintingStart.x)
                        const dy = Math.abs(e.clientY - paintingStart.y)
                        const threshold = 16 * scale

                        if (!isPaintingCollision && (dx > threshold || dy > threshold)) {
                            const x = Math.floor(e.offsetX / scale / 32)
                            const y = Math.floor(e.offsetY / scale / 32)
                            const index = y * props.map.x + x
                            const colArray = props.map.col.split('')
                            const current = colArray[index]

                            paintingMode = current === '1' ? 'remove' : 'add'
                            isPaintingCollision = true
                        }
                    }

                    if (isPaintingCollision) {
                        paintCollision(e)
                    } else {
                        handleMouseMove(e)
                    }
                }"
                                @mousedown="(e) => {
                    startPanning(e)
                    startPaintingCollision(e)
                }"
                                @mouseup="(e) => {
                    stopPanning()
                    stopPaintingCollision()
                }"
                                @mouseleave="() => {
                    stopPanning()
                    stopPaintingCollision()
                }"

                @contextmenu.prevent
                @click.self="(e) => {
                    addNewObject(e)
                }"

            >
                <div
                    :class="{
                        'pointer-events-none' : true, //todo
                        'pointer-events-auto': false
                    }"
                    ref="mouseTrackerEl"
                    class="mouse-tracker absolute bg-yellow-500/70" :style="{
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    top: trackerPosition.y * 32 * scale,
                    left: trackerPosition.x * 32 * scale,
                }" />

                <div
                    v-for="npc in npcs"
                    :key="`npc-${npc.id}-${npcScale}`"
                    class="absolute npc"
                    v-tip.npc="npc"
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
                        :src="npc.src"
                        :style="{
                            position: 'relative',
                            width: `${npcWidths[npc.id] * scale}px`,
                            bottom: 0,
                            zIndex: 1,
                            left: (32 - npcWidths[npc.id]) * scale / 2,
                            border: npc.group_id !== null ? `4px dashed ${getGroupColor(npc.group_id)}` : 'none',
                              borderRadius: npc.group_id !== null ? '8px' : '',
                              boxShadow: npc.group_id !== null ? `0 0 10px ${getGroupColor(npc.group_id)}` : ''

                        }"
                        @load="adjustNpcOffset(npc.id, $event.target as HTMLImageElement)"
                        alt="npc" />
                </div>


                <div
                    class="door"
                    v-for="door in doors"
                    v-tooltip="'Przejście do: ' + door.name + ' (' + door.go_x + ',' + door.go_y + '), \nPowrót: ' + (door.double_sided ? 'Tak' : 'Nie' )"
                    :style="{
                        width: `${32 * scale}px`,
                        height: `${32 * scale}px`,
                        top: `${door.y * 32 * scale}px`,
                        left: `${door.x * 32 * scale}px`,
                    }"
                    :class="{'double-sided': door.double_sided}"
                    @click="showDoorConfirmDialog($event, door)"
                />

                <div
                    v-for="(pos, index) in collisionPositions"
                    :key="index"
                    class="col col-opacity"
                    :class="{
                        'edit-cols': editColsOn
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

        <div class="card">
            <pre>{{npcs}}</pre>
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

.group-border {
    border: #777bf1 dashed 3px;
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

.edit-cols {
    background-color: pink;
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
