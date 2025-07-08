<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapResource } from '@/Resources/Map.resource';
import { ref } from 'vue';
import { useConfirm } from 'primevue';
import ItemHeader from "@/Components/ItemHeader.vue";
import { NpcWithLocationResource } from '@/Resources/Npc.resource';
import { DoorResource } from '@/Resources/Door.resource';
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";

// Import components
import MapInformation from './Components/MapInformation.vue';
import MapControls from './Components/MapControls.vue';
import MapCoordinates from './Components/MapCoordinates.vue';
import NpcConfirmPopup from './Components/NpcConfirmPopup.vue';
import DoorConfirmPopup from './Components/DoorConfirmPopup.vue';
import MapContainer from './Components/MapContainer.vue';
import RemoveMap from './Partials/RemoveMap.vue';

const props = defineProps<{
    map: MapResource;
    npcs: NpcWithLocationResource[];
    doors: DoorResource[];
    pvpTypeList: { value: number; label: string }[];
    respawnPoints: { id: number; map_id: number; map_name: string; x: number; y: number }[];
    doorsLeadingToMap: DoorResource[];
    dialogNodesTeleportingToMap: any[];
    itemsTeleportingToMap: any[];
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

// Handle edit water change
const handleEditWaterChanged = (value: boolean) => {
    if (mapContainerRef.value) {
        mapContainerRef.value.setEditWaterOn(value);
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
            @edit-water-changed="handleEditWaterChanged"
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

        <Tabs value="0" class="card">
            <TabList>
                <Tab value="0">Drzwi prowadzące na tę mapę</Tab>
                <Tab value="1">Dialogi teleportujące na tę mapę</Tab>
                <Tab value="2">Przedmioty teleportujące na tę mapę</Tab>
            </TabList>
            <TabPanels>
                <TabPanel value="0">
                    <DetailsCardList title="Drzwi prowadzące na tę mapę" v-if="doorsLeadingToMap.length > 0">
                        <DetailsCardListItem v-for="door in doorsLeadingToMap" :key="door.id" :label="door.map?.name || 'Nieznana mapa'">
                            <template #value>
                                <div class="flex flex-col">
                                    <span>Z pozycji: X: {{ door.x }}, Y: {{ door.y }}</span>
                                    <span>Na pozycję: X: {{ door.go_x }}, Y: {{ door.go_y }}</span>
                                    <span v-if="door.min_lvl || door.max_lvl">Poziom: {{ door.min_lvl || 'min' }} - {{ door.max_lvl || 'max' }}</span>
                                    <span v-if="door.requiredBaseItem">Wymagany przedmiot: {{ door.requiredBaseItem.name }}</span>
                                </div>
                            </template>
                        </DetailsCardListItem>
                    </DetailsCardList>
                    <Message v-else severity="warn">Brak przejść prowadzących do tej lokalizacji</Message>
                </TabPanel>
                <TabPanel value="1">
                    <DetailsCardList title="Dialogi teleportujące na tę mapę" v-if="dialogNodesTeleportingToMap.length > 0">
                        <DetailsCardListItem v-for="node in dialogNodesTeleportingToMap" :key="node.id" :label="node.dialog?.name || 'Nieznany dialog'">
                            <template #value>
                                <div class="flex flex-col">
                                    <span>Treść: {{ node.content }}</span>
                                    <span>Pozycja docelowa: X: {{ node.action_data?.teleportation?.x }}, Y: {{ node.action_data?.teleportation?.y }}</span>
                                </div>
                            </template>
                        </DetailsCardListItem>
                    </DetailsCardList>
                    <Message v-else severity="warn">Brak dialogów prowadzących do tej lokalizacji</Message>
                </TabPanel>
                <TabPanel value="2">
                    <DetailsCardList title="Przedmioty teleportujące na tę mapę" v-if="itemsTeleportingToMap.length > 0">
                        <DetailsCardListItem v-for="item in itemsTeleportingToMap" :key="item.id" :label="item.name">
                            <template #value>
                                <div class="flex flex-col">
                                    <span>Pozycja docelowa: X: {{ item.attributes?.teleportTo?.[1] }}, Y: {{ item.attributes?.teleportTo?.[2] }}</span>
                                    <span v-if="item.attributes?.teleportTo?.[3]">Nazwa mapy: {{ item.attributes?.teleportTo?.[3] }}</span>
                                    <span v-if="item.attributes?.cooldownTime">Cooldown: {{ item.attributes?.cooldownTime?.[0] }}s</span>
                                </div>
                            </template>
                        </DetailsCardListItem>
                    </DetailsCardList>
                    <Message v-else severity="warn">Brak przedmiotów teleportujących do tej lokalizacji</Message>
                </TabPanel>
            </TabPanels>
        </Tabs>

        <RemoveMap :map="map" />

    </AppLayout>
</template>
