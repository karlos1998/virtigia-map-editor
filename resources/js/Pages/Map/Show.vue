<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapResource } from '@/Resources/Map.resource';
import { computed, ref } from 'vue';
import { useConfirm, useToast } from 'primevue';
import { router } from '@inertiajs/vue3';
import InputNumber from 'primevue/inputnumber';
import { route } from 'ziggy-js';
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
    renewableItems: any[];
}>();

// Map state
const scale = ref(1);
const trackerPosition = ref({ x: 0, y: 0 });
const confirm = useConfirm();
const toast = useToast();
const mapContainerRef = ref(null);
const naturalNpcSize = ref(false);

type NpcDrawOffset = {
    x: number;
    y: number;
};

type TileEditorLayer = 'cols' | 'water';
type TileEditorTool = 'brush' | 'rectangle' | 'preset';
type TileEditorCommand = 'undo' | 'redo' | 'fill-selection' | 'erase-selection' | 'clear-selection' | 'save-preset' | 'apply-preset';

type TileEditorSettings = {
    tool: TileEditorTool;
    brushSize: number;
    waterDepth: number;
    selectedPresetId: string | null;
};

type TileEditorPresetOption = {
    id: string;
    name: string;
    layer: TileEditorLayer;
    width: number;
    height: number;
    tileCount: number;
};

type TileEditorState = {
    canUndo: boolean;
    canRedo: boolean;
    presets: TileEditorPresetOption[];
    selectionSummary: string | null;
};

const offsetDialogVisible = ref(false);
const offsetNpc = ref<NpcWithLocationResource | null>(null);
const draftDrawOffsetX = ref(0);
const draftDrawOffsetY = ref(0);
const previewNpcDrawOffsets = ref<Record<number, NpcDrawOffset>>({});
const savedNpcDrawOffsets = ref<Record<number, NpcDrawOffset>>({});
const offsetLimit = 256;
const tileEditorState = ref<TileEditorState>({
    canUndo: false,
    canRedo: false,
    presets: [],
    selectionSummary: null,
});

const activeBaseNpcId = computed(() => offsetNpc.value?.base_npc_id ?? null);

const npcDrawOffsetOverrides = computed<Record<number, NpcDrawOffset>>(() => ({
    ...savedNpcDrawOffsets.value,
    ...previewNpcDrawOffsets.value,
}));

const clampOffset = (value: number | null): number => {
    const numericValue = Number(value ?? 0);

    if (!Number.isFinite(numericValue)) {
        return 0;
    }

    return Math.max(-offsetLimit, Math.min(offsetLimit, Math.trunc(numericValue)));
};

const setPreviewForActiveNpc = () => {
    if (activeBaseNpcId.value === null) {
        return;
    }

    previewNpcDrawOffsets.value = {
        ...previewNpcDrawOffsets.value,
        [activeBaseNpcId.value]: {
            x: draftDrawOffsetX.value,
            y: draftDrawOffsetY.value,
        },
    };
};

const clearPreviewForActiveNpc = () => {
    if (activeBaseNpcId.value === null) {
        return;
    }

    const nextOffsets = { ...previewNpcDrawOffsets.value };
    delete nextOffsets[activeBaseNpcId.value];
    previewNpcDrawOffsets.value = nextOffsets;
};

const getStoredDrawOffset = (npc: NpcWithLocationResource): NpcDrawOffset => {
    return savedNpcDrawOffsets.value[npc.base_npc_id] ?? {
        x: npc.draw_offset_x ?? 0,
        y: npc.draw_offset_y ?? 0,
    };
};

const openDrawOffsetDialog = (npc: NpcWithLocationResource) => {
    const offset = getStoredDrawOffset(npc);

    offsetNpc.value = npc;
    draftDrawOffsetX.value = offset.x;
    draftDrawOffsetY.value = offset.y;
    offsetDialogVisible.value = true;
    setPreviewForActiveNpc();
};

const updateDraftDrawOffset = (axis: 'x' | 'y', value: number | null) => {
    if (axis === 'x') {
        draftDrawOffsetX.value = clampOffset(value);
    } else {
        draftDrawOffsetY.value = clampOffset(value);
    }

    setPreviewForActiveNpc();
};

const nudgeDrawOffset = (axis: 'x' | 'y', delta: number) => {
    updateDraftDrawOffset(axis, (axis === 'x' ? draftDrawOffsetX.value : draftDrawOffsetY.value) + delta);
};

const resetDrawOffset = () => {
    draftDrawOffsetX.value = 0;
    draftDrawOffsetY.value = 0;
    setPreviewForActiveNpc();
};

const closeDrawOffsetDialog = () => {
    clearPreviewForActiveNpc();
    offsetDialogVisible.value = false;
    offsetNpc.value = null;
};

const saveDrawOffset = () => {
    if (activeBaseNpcId.value === null) {
        return;
    }

    const baseNpcId = activeBaseNpcId.value;
    const offset = {
        x: draftDrawOffsetX.value,
        y: draftDrawOffsetY.value,
    };

    router.patch(route('base-npcs.draw-offset.update', baseNpcId), {
        draw_offset_x: offset.x,
        draw_offset_y: offset.y,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            savedNpcDrawOffsets.value = {
                ...savedNpcDrawOffsets.value,
                [baseNpcId]: offset,
            };
            toast.add({ severity: 'success', summary: 'Zapisano', detail: 'Offset NPC został zapisany', life: 3000 });
            closeDrawOffsetDialog();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zapisać offsetu NPC', life: 4000 });
        },
    });
};

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

const handleNaturalNpcSizeChanged = (value: boolean) => {
    naturalNpcSize.value = value;
};

const handleTileEditorSettingsChanged = (settings: TileEditorSettings) => {
    if (mapContainerRef.value) {
        mapContainerRef.value.setTileEditorSettings(settings);
    }
};

const handleTileEditorCommand = (command: TileEditorCommand) => {
    if (mapContainerRef.value) {
        mapContainerRef.value.runTileEditorCommand(command);
    }
};

const handleTileEditorStateChanged = (state: TileEditorState) => {
    tileEditorState.value = state;
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
        <NpcConfirmPopup
            @move-npc="handleMoveNpc"
            @add-to-group="handleAddNpcToGroup"
            @adjust-draw-offset="openDrawOffsetDialog"
        />

        <Dialog
            v-model:visible="offsetDialogVisible"
            modal
            header="Lekkie przesunięcie NPC"
            :style="{ width: '28rem' }"
            @hide="closeDrawOffsetDialog"
        >
            <div v-if="offsetNpc" class="flex flex-col gap-5">
                <div class="flex items-center gap-3">
                    <img :src="offsetNpc.src" alt="npc" class="max-w-16 max-h-16 object-contain" />
                    <div class="min-w-0">
                        <div class="font-semibold truncate">{{ offsetNpc.name }}</div>
                        <div class="text-sm text-surface-500">Base NPC #{{ offsetNpc.base_npc_id }}</div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="w-8 font-medium">X</span>
                        <Button icon="pi pi-arrow-left" severity="secondary" outlined aria-label="Lewo" @click="nudgeDrawOffset('x', -1)" />
                        <InputNumber
                            :model-value="draftDrawOffsetX"
                            :min="-offsetLimit"
                            :max="offsetLimit"
                            show-buttons
                            class="w-32"
                            suffix=" px"
                            @update:model-value="(value) => updateDraftDrawOffset('x', value)"
                        />
                        <Button icon="pi pi-arrow-right" severity="secondary" outlined aria-label="Prawo" @click="nudgeDrawOffset('x', 1)" />
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="w-8 font-medium">Y</span>
                        <Button icon="pi pi-arrow-up" severity="secondary" outlined aria-label="Góra" @click="nudgeDrawOffset('y', -1)" />
                        <InputNumber
                            :model-value="draftDrawOffsetY"
                            :min="-offsetLimit"
                            :max="offsetLimit"
                            show-buttons
                            class="w-32"
                            suffix=" px"
                            @update:model-value="(value) => updateDraftDrawOffset('y', value)"
                        />
                        <Button icon="pi pi-arrow-down" severity="secondary" outlined aria-label="Dół" @click="nudgeDrawOffset('y', 1)" />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between w-full">
                    <Button label="Reset" severity="secondary" text @click="resetDrawOffset" />
                    <div class="flex gap-2">
                        <Button label="Anuluj" severity="secondary" outlined @click="closeDrawOffsetDialog" />
                        <Button label="Zapisz" @click="saveDrawOffset" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Door Confirm Popup -->
        <DoorConfirmPopup @move-door="handleMoveDoor" />

        <ItemHeader :route-back="route('maps.index')">
            <template #header>
                #{{ map.id }} - {{ map.name }}
            </template>
            <template #right-buttons>
                <button
                    class="px-4 py-2 text-white bg-green-500 hover:bg-green-600 rounded shadow"
                    @click="() => {
                        router.post(route('maps.copy', map.id));
                    }"
                >
                    <i class="pi pi-copy mr-2"></i>
                    Kopiuj mapę
                </button>
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
            :natural-npc-size="naturalNpcSize"
            :tile-editor-presets="tileEditorState.presets"
            :can-undo-tile-edit="tileEditorState.canUndo"
            :can-redo-tile-edit="tileEditorState.canRedo"
            :tile-selection-summary="tileEditorState.selectionSummary"
            @zoom-in="zoomIn"
            @zoom-out="zoomOut"
            @edit-cols-changed="handleEditColsChanged"
            @edit-water-changed="handleEditWaterChanged"
            @natural-npc-size-changed="handleNaturalNpcSizeChanged"
            @tile-editor-settings-changed="handleTileEditorSettingsChanged"
            @tile-editor-command="handleTileEditorCommand"
        />

        <!-- Map Container -->
        <MapContainer
            ref="mapContainerRef"
            :map="map"
            :npcs="npcs"
            :doors="doors"
            :renewable-items="renewableItems"
            :scale="scale"
            :natural-npc-size="naturalNpcSize"
            :npc-draw-offset-overrides="npcDrawOffsetOverrides"
            @show-npc-confirm-dialog="showNpcConfirmDialog"
            @show-door-confirm-dialog="showDoorConfirmDialog"
            @tracker-position-changed="handleTrackerPositionChanged"
            @tile-editor-state-changed="handleTileEditorStateChanged"
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
