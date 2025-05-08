<script setup lang="ts">
import { ref, computed } from 'vue';
import { MapResource } from '@/Resources/Map.resource';
import { NpcWithLocationResource } from '@/Resources/Npc.resource';
import { DoorResource } from '@/Resources/Door.resource';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useDialog } from 'primevue/usedialog';
import { DynamicDialogCloseOptions, DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import AddNpcToMap from "@/Pages/Map/Modals/AddNpcToMap.vue";
import TeleportationSelectModal from "@/Components/TeleportationSelectModal.vue";
import { DialogNodeTeleportationDataResource } from "@/Resources/DialogNodeTeleportationData.resource";
import { useToast, useConfirm } from 'primevue';
import NpcRenderer from './NpcRenderer.vue';
import DoorRenderer from './DoorRenderer.vue';
import CollisionRenderer from './CollisionRenderer.vue';

const props = defineProps<{
    map: MapResource;
    npcs: NpcWithLocationResource[];
    doors: DoorResource[];
    scale: number;
}>();

const emit = defineEmits<{
    (e: 'showNpcConfirmDialog', event: MouseEvent, npc: NpcWithLocationResource): void;
    (e: 'showDoorConfirmDialog', event: MouseEvent, door: DoorResource): void;
    (e: 'trackerPositionChanged', position: { x: number, y: number }): void;
}>();

const primeDialog = useDialog();
const toast = useToast();
const confirm = useConfirm();

// Map state
const isMapVisible = ref(true);
const npcScale = ref(true);
const editColsOn = ref(false);

// Mouse tracking
const trackerPosition = ref({ x: 0, y: 0 });
const mouseTrackerEl = ref<HTMLElement | null>(null);

// Panning
const isPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });
const mapOffset = ref({ x: 0, y: 0 });

// Collision painting
const isPaintingCollision = ref(false);
const paintingMode = ref<'add' | 'remove' | null>(null);
const paintingStart = ref<{ x: number, y: number } | null>(null);

// NPC and Door movement
const moveNpcLocationData = ref<NpcWithLocationResource>(null);
const moveDoorLocationData = ref<DoorResource>(null);

// NPC dialog
const addNpcToMapDialogInstance = ref<DynamicDialogInstance>();
const lastSelectedNpc = ref<NpcWithLocationResource>();

// Set tracker position based on mouse coordinates
const setTrackerPosition = (event: MouseEvent) => {
    if (event.target !== event.currentTarget) {
        trackerPosition.value = { x: -32, y: -32 };
        return;
    }
    trackerPosition.value = {
        x: (event.offsetX / props.scale / 32) | 0,
        y: (event.offsetY / props.scale / 32) | 0
    };
    emit('trackerPositionChanged', trackerPosition.value);
};

// Handle mouse movement
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

// Start panning the map
const startPanning = (event: MouseEvent) => {
    if (event.button === 2) { // Right mouse button
        isPanning.value = true;
        panStart.value = { x: event.clientX - mapOffset.value.x, y: event.clientY - mapOffset.value.y };
        event.preventDefault();
    }
};

// Stop panning the map
const stopPanning = () => {
    isPanning.value = false;
};

// Toggle collision at a specific position
const toggleCollision = (x: number, y: number) => {
    const index = y * props.map.x + x;
    const colArray = props.map.col.split('');
    colArray[index] = colArray[index] === '0' ? '1' : '0';
    props.map.col = colArray.join('');
};

// Paint collision based on mouse movement
const paintCollision = (event: MouseEvent) => {
    if (!editColsOn.value || !isPaintingCollision.value || !paintingMode.value) return;

    const x = Math.floor(event.offsetX / props.scale / 32);
    const y = Math.floor(event.offsetY / props.scale / 32);
    const index = y * props.map.x + x;
    const colArray = props.map.col.split('');

    if (paintingMode.value === 'add' && colArray[index] === '0') {
        colArray[index] = '1';
        props.map.col = colArray.join('');
    } else if (paintingMode.value === 'remove' && colArray[index] === '1') {
        colArray[index] = '0';
        props.map.col = colArray.join('');
    }
};

// Start painting collision
const startPaintingCollision = (event: MouseEvent) => {
    if (!editColsOn.value || event.button !== 0) return;

    paintingStart.value = {
        x: event.clientX,
        y: event.clientY,
    };
};

// Stop painting collision
const stopPaintingCollision = () => {
    isPaintingCollision.value = false;
    paintingMode.value = null;
    paintingStart.value = null;
};

// Add a new object (NPC or door) at the current tracker position
const addNewObject = (event: MouseEvent) => {
    const x = trackerPosition.value.x;
    const y = trackerPosition.value.y;

    if (moveNpcLocationData.value) {
        updateMoveNpcLocation(x, y);
        return;
    }

    if (moveDoorLocationData.value) {
        updateMoveDoorLocation(x, y);
        return;
    }

    if (editColsOn.value) {
        toggleCollision(x, y);
        return;
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
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { npc?: NpcWithLocationResource } }) {
            if (closeOptions.data && closeOptions.data.npc) {
                lastSelectedNpc.value = closeOptions.data.npc;
            } else if (closeOptions.data?.addDoor) {
                addDoorTo(x, y);
            }
        }
    });
};

// Add a door at the specified position
const addDoorTo = (x: number, y: number) => {
    primeDialog.open(TeleportationSelectModal, {
        props: {
            header: 'Edycja miejsca teleportacji',
            modal: true,
            breakpoints: {
                '960px': '75vw',
                '640px': '90vw'
            },
            style: 'max-width:90%'
        },
        data: {},
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { teleportation: DialogNodeTeleportationDataResource } }) {
            if (closeOptions.data?.teleportation) {
                router.post(route('doors.store'), {
                    map_id: props.map.id,
                    x,
                    y,
                    go_map_id: closeOptions.data.teleportation.mapId,
                    go_x: closeOptions.data.teleportation.x,
                    go_y: closeOptions.data.teleportation.y,
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
                                    x: closeOptions.data.teleportation.x,
                                    y: closeOptions.data.teleportation.y,
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
                                });
                            }
                        });
                    },
                    onError: () => {
                        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się umieścić przejścia', life: 6000 });
                    }
                });
            }
        }
    });
};

// Update NPC location
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
    });
    moveNpcLocationData.value = null;
};

// Update door location
const updateMoveDoorLocation = (x: number, y: number) => {
    router.patch(route('doors.move', {
        door: moveDoorLocationData.value.id,
    }), {
        x,
        y,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
    moveDoorLocationData.value = null;
};

// Public methods exposed to parent component
defineExpose({
    setEditColsOn: (value: boolean) => {
        editColsOn.value = value;
    },
    setMoveNpcLocationData: (npc: NpcWithLocationResource) => {
        moveDoorLocationData.value = null;
        moveNpcLocationData.value = npc;
    },
    setMoveDoorLocationData: (door: DoorResource) => {
        moveDoorLocationData.value = door;
        moveNpcLocationData.value = null;
    }
});
</script>

<template>
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
                    const dx = Math.abs(e.clientX - paintingStart.x);
                    const dy = Math.abs(e.clientY - paintingStart.y);
                    const threshold = 16 * scale;

                    if (!isPaintingCollision && (dx > threshold || dy > threshold)) {
                        const x = Math.floor(e.offsetX / scale / 32);
                        const y = Math.floor(e.offsetY / scale / 32);
                        const index = y * props.map.x + x;
                        const colArray = props.map.col.split('');
                        const current = colArray[index];

                        paintingMode = current === '1' ? 'remove' : 'add';
                        isPaintingCollision = true;
                    }
                }

                if (isPaintingCollision) {
                    paintCollision(e);
                } else {
                    handleMouseMove(e);
                }
            }"
            @mousedown="(e) => {
                startPanning(e);
                startPaintingCollision(e);
            }"
            @mouseup="(e) => {
                stopPanning();
                stopPaintingCollision();
            }"
            @mouseleave="() => {
                stopPanning();
                stopPaintingCollision();
            }"
            @contextmenu.prevent
            @click.self="(e) => {
                addNewObject(e);
            }"
        >
            <!-- Mouse tracker -->
            <div
                :class="{
                    'pointer-events-none': true,
                }"
                ref="mouseTrackerEl"
                class="mouse-tracker absolute bg-yellow-500/70"
                :style="{
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    top: trackerPosition.y * 32 * scale,
                    left: trackerPosition.x * 32 * scale,
                }"
            />

            <!-- NPCs -->
            <NpcRenderer
                :npcs="npcs"
                :scale="scale"
                :npc-scale="npcScale"
                @show-npc-confirm-dialog="(event, npc) => emit('showNpcConfirmDialog', event, npc)"
            />

            <!-- Doors -->
            <DoorRenderer
                :doors="doors"
                :scale="scale"
                @show-door-confirm-dialog="(event, door) => emit('showDoorConfirmDialog', event, door)"
            />

            <!-- Collisions -->
            <CollisionRenderer
                :map="map"
                :scale="scale"
                :edit-cols-on="editColsOn"
            />
        </div>
    </div>
</template>

<style scoped>
.map-container {
    background-size: contain;
    background-repeat: no-repeat;
    background-position: top left;
    position: relative;
}

.mouse-tracker {
    pointer-events: none;
}
</style>
