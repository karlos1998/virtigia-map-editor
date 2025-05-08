<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapResource } from '@/Resources/Map.resource';
import { ref } from 'vue';
import { useConfirm } from 'primevue';
import ItemHeader from "@/Components/ItemHeader.vue";
import { NpcWithLocationResource } from '@/Resources/Npc.resource';
import { DoorResource } from '@/Resources/Door.resource';

// Import components
import MapInformation from './Components/MapInformation.vue';
import MapControls from './Components/MapControls.vue';
import MapCoordinates from './Components/MapCoordinates.vue';
import NpcConfirmPopup from './Components/NpcConfirmPopup.vue';
import DoorConfirmPopup from './Components/DoorConfirmPopup.vue';
import MapContainer from './Components/MapContainer.vue';

const props = defineProps<{
    map: MapResource;
    npcs: NpcWithLocationResource[];
    doors: DoorResource[];
    pvpTypeList: { value: number; label: string }[];
    respawnPoints: { id: number; map_id: number; map_name: string; x: number; y: number }[];
}>();

// Map state
const scale = ref(1);
const trackerPosition = ref({ x: 0, y: 0 });
const confirm = useConfirm();
const mapContainerRef = ref(null);

// Zoom functions
const zoomIn = () => {
    scale.value = Math.min(scale.value + 0.1, 2);
};

const zoomOut = () => {
    scale.value = Math.max(scale.value - 0.1, 0.5);
};

// Handle edit cols change
const handleEditColsChanged = (value: boolean) => {
    if (mapContainerRef.value) {
        mapContainerRef.value.setEditColsOn(value);
    }
};

// Handle NPC confirm dialog
const showNpcConfirmDialog = (event: MouseEvent, npc: NpcWithLocationResource) => {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        group: 'npc',
        npc,
    });
};

// Handle adding NPC to group
const handleAddNpcToGroup = (npc: NpcWithLocationResource) => {
    if (mapContainerRef.value) {
        mapContainerRef.value.setAddToGroupMode(npc);
    }
};

// Handle Door confirm dialog
const showDoorConfirmDialog = (event: MouseEvent, door: DoorResource) => {
    confirm.require({
        target: event.currentTarget as HTMLElement,
        group: 'door',
        door,
    });
};

// Handle NPC movement
const handleMoveNpc = (npc: NpcWithLocationResource) => {
    if (mapContainerRef.value) {
        mapContainerRef.value.setMoveNpcLocationData(npc);
    }
};

// Handle Door movement
const handleMoveDoor = (door: DoorResource) => {
    if (mapContainerRef.value) {
        mapContainerRef.value.setMoveDoorLocationData(door);
    }
};

// Update tracker position
const handleTrackerPositionChanged = (position: { x: number, y: number }) => {
    trackerPosition.value = position;
};



</script>

<template>
    <AppLayout>
        <ConfirmDialog />

        <!-- NPC Confirm Popup -->
        <NpcConfirmPopup @move-npc="handleMoveNpc" @add-to-group="handleAddNpcToGroup" />

        <!-- Door Confirm Popup -->
        <DoorConfirmPopup @move-door="handleMoveDoor" />

        <ItemHeader :route-back="route('maps.index')">
            <template #header>
                #{{ map.id }} - {{ map.name }}
            </template>
        </ItemHeader>

        <Message class="my-6">
            Protip: Klikając na przejścia (czarne kwadraciki) przeniesie nas na konkretną mapkę bez potrzeby jej wyszukiwania.
        </Message>

        <!-- Map Information -->
        <MapInformation
            :map="map"
            :pvp-type-list="pvpTypeList"
            :respawn-points="respawnPoints"
        />

        <!-- Map Coordinates -->
        <MapCoordinates :x="trackerPosition.x" :y="trackerPosition.y" />

        <!-- Map Controls -->
        <MapControls
            :map="map"
            :scale="scale"
            @zoom-in="zoomIn"
            @zoom-out="zoomOut"
            @edit-cols-changed="handleEditColsChanged"
        />

        <!-- Map Container -->
        <MapContainer
            ref="mapContainerRef"
            :map="map"
            :npcs="npcs"
            :doors="doors"
            :scale="scale"
            @show-npc-confirm-dialog="showNpcConfirmDialog"
            @show-door-confirm-dialog="showDoorConfirmDialog"
            @tracker-position-changed="handleTrackerPositionChanged"
        />
    </AppLayout>
</template>
