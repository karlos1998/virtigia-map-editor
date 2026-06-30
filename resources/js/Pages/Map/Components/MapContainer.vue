<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
import WaterRenderer from './WaterRenderer.vue';

type NpcDrawOffset = {
    x: number;
    y: number;
};

type TileEditorLayer = 'cols' | 'water';
type TileEditorTool = 'brush' | 'rectangle' | 'preset';
type TileEditorAction = 'paint' | 'erase';
type TileEditorCommand = 'undo' | 'redo' | 'fill-selection' | 'erase-selection' | 'clear-selection' | 'save-preset' | 'apply-preset';

type TilePosition = {
    x: number;
    y: number;
};

type TileEditorSettings = {
    tool: TileEditorTool;
    brushSize: number;
    waterDepth: number;
    selectedPresetId: string | null;
};

type TileSnapshot = {
    col: string;
    water: string;
};

type TileEditorPresetTile = {
    x: number;
    y: number;
    depth?: number;
};

type TileEditorPreset = {
    id: string;
    name: string;
    layer: TileEditorLayer;
    width: number;
    height: number;
    tiles: TileEditorPresetTile[];
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

const props = defineProps<{
    map: MapResource;
    npcs: NpcWithLocationResource[];
    doors: DoorResource[];
    renewableItems: any[];
    scale: number;
    naturalNpcSize: boolean;
    npcDrawOffsetOverrides?: Record<number, NpcDrawOffset>;
}>();

const emit = defineEmits<{
    (e: 'showNpcConfirmDialog', event: MouseEvent, npc: NpcWithLocationResource): void;
    (e: 'showDoorConfirmDialog', event: MouseEvent, door: DoorResource): void;
    (e: 'trackerPositionChanged', position: { x: number, y: number }): void;
    (e: 'tileEditorStateChanged', state: TileEditorState): void;
}>();

const primeDialog = useDialog();
const toast = useToast();
const confirm = useConfirm();

// Map state
const isMapVisible = ref(true);
const npcScale = ref(true);
const editColsOn = ref(false);
const editWaterOn = ref(false);

// Mouse tracking
const trackerPosition = ref({ x: 0, y: 0 });
const mouseTrackerEl = ref<HTMLElement | null>(null);

// Panning
const isPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });
const mapOffset = ref({ x: 0, y: 0 });

// Tile editor
const mapElementRef = ref<HTMLElement | null>(null);
const tileEditorSettings = ref<TileEditorSettings>({
    tool: 'brush',
    brushSize: 1,
    waterDepth: 1,
    selectedPresetId: null,
});
const isPaintingTiles = ref(false);
const activeBrushAction = ref<TileEditorAction | null>(null);
const activeHistorySnapshot = ref<TileSnapshot | null>(null);
const rectangleSelection = ref<{ start: TilePosition; end: TilePosition } | null>(null);
const isSelectingRectangle = ref(false);
const undoStack = ref<TileSnapshot[]>([]);
const redoStack = ref<TileSnapshot[]>([]);
const tileEditorPresets = ref<TileEditorPreset[]>([]);
const presetStorageKey = 'virtigia-map-editor.tile-editor-presets';
const historyLimit = 80;

// NPC and Door movement
const moveNpcLocationData = ref<NpcWithLocationResource>(null);
const moveDoorLocationData = ref<DoorResource>(null);

// NPC grouping
const addToGroupMode = ref<boolean>(false);
const sourceNpc = ref<NpcWithLocationResource>(null);

// NPC dialog
const addNpcToMapDialogInstance = ref<DynamicDialogInstance>();
const lastSelectedNpc = ref<NpcWithLocationResource>();

const activeTileLayer = computed<TileEditorLayer | null>(() => {
    if (editColsOn.value) {
        return 'cols';
    }

    if (editWaterOn.value) {
        return 'water';
    }

    return null;
});

const isTileEditingOn = computed(() => activeTileLayer.value !== null);

const clampInteger = (value: number, min: number, max: number): number => {
    if (!Number.isFinite(value)) {
        return min;
    }

    return Math.max(min, Math.min(max, Math.trunc(value)));
};

const tileKey = (tile: TilePosition): string => `${tile.x},${tile.y}`;

const isInsideMap = (tile: TilePosition): boolean => {
    return tile.x >= 0 && tile.y >= 0 && tile.x < props.map.x && tile.y < props.map.y;
};

const getCollisionArray = (): string[] => {
    const expectedLength = props.map.x * props.map.y;

    return (props.map.col ?? '')
        .padEnd(expectedLength, '0')
        .slice(0, expectedLength)
        .split('');
};

const getCollisionAt = (tile: TilePosition): boolean => {
    if (!isInsideMap(tile)) {
        return false;
    }

    return getCollisionArray()[tile.y * props.map.x + tile.x] === '1';
};

const parseWaterData = (water: string | undefined): Map<string, number> => {
    const waterMap = new Map<string, number>();

    if (!water) {
        return waterMap;
    }

    water.split('|').forEach((segment) => {
        const [x1, x2, y, depth] = segment.split(',').map(Number);

        if (![x1, x2, y, depth].every(Number.isFinite)) {
            return;
        }

        const normalizedDepth = clampInteger(depth, 1, 9);
        const startX = clampInteger(Math.min(x1, x2), 0, props.map.x - 1);
        const endX = clampInteger(Math.max(x1, x2), 0, props.map.x - 1);

        if (y < 0 || y >= props.map.y) {
            return;
        }

        for (let x = startX; x <= endX; x++) {
            waterMap.set(`${x},${y}`, normalizedDepth);
        }
    });

    return waterMap;
};

const serializeWaterData = (waterMap: Map<string, number>): string => {
    const waterTiles = Array.from(waterMap.entries())
        .map(([key, depth]) => {
            const [x, y] = key.split(',').map(Number);

            return { x, y, depth };
        })
        .filter((tile) => isInsideMap(tile) && tile.depth > 0)
        .sort((a, b) => a.y - b.y || a.depth - b.depth || a.x - b.x);

    const segments: string[] = [];
    let activeSegment: { x1: number; x2: number; y: number; depth: number } | null = null;

    waterTiles.forEach((tile) => {
        if (
            activeSegment
            && activeSegment.y === tile.y
            && activeSegment.depth === tile.depth
            && activeSegment.x2 + 1 === tile.x
        ) {
            activeSegment.x2 = tile.x;
            return;
        }

        if (activeSegment) {
            segments.push(`${activeSegment.x1},${activeSegment.x2},${activeSegment.y},${activeSegment.depth}`);
        }

        activeSegment = {
            x1: tile.x,
            x2: tile.x,
            y: tile.y,
            depth: clampInteger(tile.depth, 1, 9),
        };
    });

    if (activeSegment) {
        segments.push(`${activeSegment.x1},${activeSegment.x2},${activeSegment.y},${activeSegment.depth}`);
    }

    return segments.join('|');
};

const createSnapshot = (): TileSnapshot => ({
    col: props.map.col,
    water: props.map.water ?? '',
});

const snapshotsAreEqual = (snapshot: TileSnapshot): boolean => {
    return snapshot.col === props.map.col && snapshot.water === (props.map.water ?? '');
};

const restoreSnapshot = (snapshot: TileSnapshot): void => {
    props.map.col = snapshot.col;
    props.map.water = snapshot.water;
};

const pushUndoSnapshot = (snapshot: TileSnapshot): void => {
    undoStack.value = [...undoStack.value.slice(-(historyLimit - 1)), snapshot];
    redoStack.value = [];
};

const beginHistoryEntry = (): void => {
    if (activeHistorySnapshot.value) {
        return;
    }

    activeHistorySnapshot.value = createSnapshot();
};

const finishHistoryEntry = (): void => {
    if (!activeHistorySnapshot.value) {
        return;
    }

    if (!snapshotsAreEqual(activeHistorySnapshot.value)) {
        pushUndoSnapshot(activeHistorySnapshot.value);
    }

    activeHistorySnapshot.value = null;
};

const runWithHistory = (callback: () => void): void => {
    const snapshot = createSnapshot();

    callback();

    if (!snapshotsAreEqual(snapshot)) {
        pushUndoSnapshot(snapshot);
    }
};

const undoTileEdit = (): void => {
    finishHistoryEntry();

    const snapshot = undoStack.value[undoStack.value.length - 1];

    if (!snapshot) {
        return;
    }

    undoStack.value = undoStack.value.slice(0, -1);
    redoStack.value = [...redoStack.value, createSnapshot()];
    restoreSnapshot(snapshot);
};

const redoTileEdit = (): void => {
    finishHistoryEntry();

    const snapshot = redoStack.value[redoStack.value.length - 1];

    if (!snapshot) {
        return;
    }

    redoStack.value = redoStack.value.slice(0, -1);
    undoStack.value = [...undoStack.value, createSnapshot()];
    restoreSnapshot(snapshot);
};

const getTileFromMouseEvent = (event: MouseEvent): TilePosition | null => {
    const mapElement = mapElementRef.value;

    if (!mapElement) {
        return null;
    }

    const bounds = mapElement.getBoundingClientRect();
    const tile = {
        x: Math.floor((event.clientX - bounds.left) / props.scale / 32),
        y: Math.floor((event.clientY - bounds.top) / props.scale / 32),
    };

    return isInsideMap(tile) ? tile : null;
};

const setTrackerPosition = (event: MouseEvent): TilePosition | null => {
    const tile = getTileFromMouseEvent(event);

    trackerPosition.value = tile ?? { x: -32, y: -32 };
    emit('trackerPositionChanged', trackerPosition.value);

    return tile;
};

const getBrushTiles = (center: TilePosition, size: number): TilePosition[] => {
    const safeSize = clampInteger(size, 1, 12);
    const offset = Math.floor((safeSize - 1) / 2);
    const tiles: TilePosition[] = [];

    for (let y = center.y - offset; y < center.y - offset + safeSize; y++) {
        for (let x = center.x - offset; x < center.x - offset + safeSize; x++) {
            const tile = { x, y };

            if (isInsideMap(tile)) {
                tiles.push(tile);
            }
        }
    }

    return tiles;
};

const getRectangleBounds = (selection: { start: TilePosition; end: TilePosition }) => ({
    minX: Math.min(selection.start.x, selection.end.x),
    maxX: Math.max(selection.start.x, selection.end.x),
    minY: Math.min(selection.start.y, selection.end.y),
    maxY: Math.max(selection.start.y, selection.end.y),
});

const selectedRectangleTiles = computed<TilePosition[]>(() => {
    if (!rectangleSelection.value) {
        return [];
    }

    const bounds = getRectangleBounds(rectangleSelection.value);
    const tiles: TilePosition[] = [];

    for (let y = bounds.minY; y <= bounds.maxY; y++) {
        for (let x = bounds.minX; x <= bounds.maxX; x++) {
            tiles.push({ x, y });
        }
    }

    return tiles;
});

const selectionOverlayStyle = computed(() => {
    if (!rectangleSelection.value) {
        return null;
    }

    const bounds = getRectangleBounds(rectangleSelection.value);

    return {
        top: `${bounds.minY * 32 * props.scale}px`,
        left: `${bounds.minX * 32 * props.scale}px`,
        width: `${(bounds.maxX - bounds.minX + 1) * 32 * props.scale}px`,
        height: `${(bounds.maxY - bounds.minY + 1) * 32 * props.scale}px`,
    };
});

const selectionSummary = computed(() => {
    if (!rectangleSelection.value) {
        return null;
    }

    const bounds = getRectangleBounds(rectangleSelection.value);

    return `${bounds.maxX - bounds.minX + 1}x${bounds.maxY - bounds.minY + 1}`;
});

const activePreset = computed(() => {
    return tileEditorPresets.value.find((preset) => preset.id === tileEditorSettings.value.selectedPresetId) ?? null;
});

const presetOptions = computed<TileEditorPresetOption[]>(() => {
    return tileEditorPresets.value.map((preset) => ({
        id: preset.id,
        name: preset.name,
        layer: preset.layer,
        width: preset.width,
        height: preset.height,
        tileCount: preset.tiles.length,
    }));
});

const emitTileEditorState = (): void => {
    emit('tileEditorStateChanged', {
        canUndo: undoStack.value.length > 0,
        canRedo: redoStack.value.length > 0,
        presets: presetOptions.value,
        selectionSummary: selectionSummary.value,
    });
};

const storePresets = (): void => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(presetStorageKey, JSON.stringify(tileEditorPresets.value));
};

const loadPresets = (): void => {
    if (typeof window === 'undefined') {
        return;
    }

    const storedPresets = window.localStorage.getItem(presetStorageKey);

    if (!storedPresets) {
        return;
    }

    try {
        const parsedPresets = JSON.parse(storedPresets) as TileEditorPreset[];

        tileEditorPresets.value = parsedPresets.filter((preset) => {
            return ['cols', 'water'].includes(preset.layer)
                && Array.isArray(preset.tiles)
                && Number.isInteger(preset.width)
                && Number.isInteger(preset.height);
        });
    } catch {
        tileEditorPresets.value = [];
    }
};

const createPresetId = (): string => {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
};

const setCollisionTiles = (tiles: TilePosition[], action: TileEditorAction): void => {
    const colArray = getCollisionArray();

    tiles.forEach((tile) => {
        if (!isInsideMap(tile)) {
            return;
        }

        colArray[tile.y * props.map.x + tile.x] = action === 'paint' ? '1' : '0';
    });

    props.map.col = colArray.join('');
};

const setWaterTiles = (tiles: TilePosition[], action: TileEditorAction): void => {
    const waterMap = parseWaterData(props.map.water);
    const depth = clampInteger(tileEditorSettings.value.waterDepth, 1, 9);

    tiles.forEach((tile) => {
        if (!isInsideMap(tile)) {
            return;
        }

        if (action === 'erase') {
            waterMap.delete(tileKey(tile));
            return;
        }

        waterMap.set(tileKey(tile), depth);
    });

    props.map.water = serializeWaterData(waterMap);
};

const applyTiles = (tiles: TilePosition[], action: TileEditorAction): void => {
    if (activeTileLayer.value === 'cols') {
        setCollisionTiles(tiles, action);
        return;
    }

    if (activeTileLayer.value === 'water') {
        setWaterTiles(tiles, action);
    }
};

const getBrushAction = (tile: TilePosition, button: number): TileEditorAction => {
    if (button === 2) {
        return 'erase';
    }

    if (activeTileLayer.value === 'cols') {
        return getCollisionAt(tile) ? 'erase' : 'paint';
    }

    return 'paint';
};

const applyBrushAt = (tile: TilePosition, action: TileEditorAction): void => {
    applyTiles(getBrushTiles(tile, tileEditorSettings.value.brushSize), action);
};

const applyRectangleSelection = (action: TileEditorAction): void => {
    if (selectedRectangleTiles.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Brak zaznaczenia', detail: 'Najpierw zaznacz obszar na mapie', life: 3000 });
        return;
    }

    runWithHistory(() => {
        applyTiles(selectedRectangleTiles.value, action);
    });
    emitTileEditorState();
};

const saveSelectionAsPreset = (): void => {
    const layer = activeTileLayer.value;

    if (!layer || !rectangleSelection.value || selectedRectangleTiles.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Brak zaznaczenia', detail: 'Najpierw zaznacz obszar do zapisania', life: 3000 });
        return;
    }

    const name = window.prompt('Nazwa presetu');

    if (!name) {
        return;
    }

    const bounds = getRectangleBounds(rectangleSelection.value);
    const tiles: TileEditorPresetTile[] = [];

    if (layer === 'cols') {
        const colArray = getCollisionArray();

        selectedRectangleTiles.value.forEach((tile) => {
            if (colArray[tile.y * props.map.x + tile.x] !== '1') {
                return;
            }

            tiles.push({
                x: tile.x - bounds.minX,
                y: tile.y - bounds.minY,
            });
        });
    } else {
        const waterMap = parseWaterData(props.map.water);

        selectedRectangleTiles.value.forEach((tile) => {
            const depth = waterMap.get(tileKey(tile));

            if (!depth) {
                return;
            }

            tiles.push({
                x: tile.x - bounds.minX,
                y: tile.y - bounds.minY,
                depth,
            });
        });
    }

    if (tiles.length === 0) {
        toast.add({ severity: 'warn', summary: 'Pusty preset', detail: 'Zaznaczenie nie zawiera aktywnych kafli', life: 3000 });
        return;
    }

    const preset: TileEditorPreset = {
        id: createPresetId(),
        name,
        layer,
        width: bounds.maxX - bounds.minX + 1,
        height: bounds.maxY - bounds.minY + 1,
        tiles,
    };

    tileEditorPresets.value = [...tileEditorPresets.value, preset];
    tileEditorSettings.value.selectedPresetId = preset.id;
    storePresets();
    emitTileEditorState();
    toast.add({ severity: 'success', summary: 'Preset zapisany', detail: preset.name, life: 3000 });
};

const applyPresetAt = (anchor: TilePosition): void => {
    const preset = activePreset.value;
    const layer = activeTileLayer.value;

    if (!preset || !layer) {
        toast.add({ severity: 'warn', summary: 'Brak presetu', detail: 'Wybierz preset przed użyciem', life: 3000 });
        return;
    }

    if (preset.layer !== layer) {
        toast.add({ severity: 'warn', summary: 'Inny typ presetu', detail: 'Preset pasuje do innej warstwy edycji', life: 3000 });
        return;
    }

    runWithHistory(() => {
        if (layer === 'cols') {
            setCollisionTiles(
                preset.tiles.map((tile) => ({ x: anchor.x + tile.x, y: anchor.y + tile.y })),
                'paint',
            );
            return;
        }

        const waterMap = parseWaterData(props.map.water);

        preset.tiles.forEach((tile) => {
            const targetTile = { x: anchor.x + tile.x, y: anchor.y + tile.y };

            if (isInsideMap(targetTile)) {
                waterMap.set(tileKey(targetTile), clampInteger(tile.depth ?? tileEditorSettings.value.waterDepth, 1, 9));
            }
        });

        props.map.water = serializeWaterData(waterMap);
    });
    emitTileEditorState();
};

const tilePreviewPositions = computed(() => {
    if (!isTileEditingOn.value || !isInsideMap(trackerPosition.value)) {
        return [];
    }

    if (tileEditorSettings.value.tool === 'brush') {
        return getBrushTiles(trackerPosition.value, tileEditorSettings.value.brushSize).map((tile) => ({
            ...tile,
            depth: activeTileLayer.value === 'water' ? tileEditorSettings.value.waterDepth : null,
        }));
    }

    if (tileEditorSettings.value.tool === 'preset' && activePreset.value?.layer === activeTileLayer.value) {
        return activePreset.value.tiles
            .map((tile) => ({
                x: trackerPosition.value.x + tile.x,
                y: trackerPosition.value.y + tile.y,
                depth: tile.depth ?? null,
            }))
            .filter(isInsideMap);
    }

    return [];
});

const startPanning = (event: MouseEvent): void => {
    if (event.button === 2 && !isTileEditingOn.value) {
        isPanning.value = true;
        panStart.value = { x: event.clientX - mapOffset.value.x, y: event.clientY - mapOffset.value.y };
        event.preventDefault();
    }
};

const stopPanning = (): void => {
    isPanning.value = false;
};

const handleTileMouseDown = (event: MouseEvent): boolean => {
    if (!isTileEditingOn.value || (event.button !== 0 && event.button !== 2)) {
        return false;
    }

    const tile = setTrackerPosition(event);

    if (!tile) {
        return true;
    }

    event.preventDefault();

    if (tileEditorSettings.value.tool === 'rectangle') {
        rectangleSelection.value = { start: tile, end: tile };
        isSelectingRectangle.value = true;
        emitTileEditorState();
        return true;
    }

    if (tileEditorSettings.value.tool === 'preset') {
        if (event.button === 0) {
            applyPresetAt(tile);
        }

        return true;
    }

    activeBrushAction.value = getBrushAction(tile, event.button);
    isPaintingTiles.value = true;
    beginHistoryEntry();
    applyBrushAt(tile, activeBrushAction.value);

    return true;
};

const finishTileMouseAction = (): void => {
    isSelectingRectangle.value = false;
    isPaintingTiles.value = false;
    activeBrushAction.value = null;
    finishHistoryEntry();
    emitTileEditorState();
};

const handleMapMouseMove = (event: MouseEvent): void => {
    if (isPanning.value) {
        mapOffset.value = {
            x: event.clientX - panStart.value.x,
            y: event.clientY - panStart.value.y,
        };
        return;
    }

    const tile = setTrackerPosition(event);

    if (!tile) {
        return;
    }

    if (isSelectingRectangle.value && rectangleSelection.value) {
        rectangleSelection.value = {
            ...rectangleSelection.value,
            end: tile,
        };
        emitTileEditorState();
        return;
    }

    if (isPaintingTiles.value && activeBrushAction.value) {
        applyBrushAt(tile, activeBrushAction.value);
    }
};

const handleMapMouseDown = (event: MouseEvent): void => {
    if (handleTileMouseDown(event)) {
        return;
    }

    startPanning(event);
};

const handleMapMouseUp = (): void => {
    stopPanning();
    finishTileMouseAction();
};

const handleMapMouseLeave = (): void => {
    trackerPosition.value = { x: -32, y: -32 };
    emit('trackerPositionChanged', trackerPosition.value);
    stopPanning();
    finishTileMouseAction();
};

const handleMapClick = (event: MouseEvent): void => {
    if (isTileEditingOn.value) {
        return;
    }

    addNewObject(event);
};

const handleMapClickCapture = (event: MouseEvent): void => {
    if (!isTileEditingOn.value) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
};

const clearRectangleSelection = (): void => {
    rectangleSelection.value = null;
    isSelectingRectangle.value = false;
    emitTileEditorState();
};

const runTileEditorCommand = (command: TileEditorCommand): void => {
    if (command === 'undo') {
        undoTileEdit();
    } else if (command === 'redo') {
        redoTileEdit();
    } else if (command === 'fill-selection') {
        applyRectangleSelection('paint');
    } else if (command === 'erase-selection') {
        applyRectangleSelection('erase');
    } else if (command === 'clear-selection') {
        clearRectangleSelection();
    } else if (command === 'save-preset') {
        saveSelectionAsPreset();
    } else if (command === 'apply-preset') {
        applyPresetAt(trackerPosition.value);
    }

    emitTileEditorState();
};

const isEditableShortcutTarget = (target: EventTarget | null): boolean => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    return ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable;
};

const handleTileEditorKeydown = (event: KeyboardEvent): void => {
    if (isEditableShortcutTarget(event.target)) {
        return;
    }

    const usesControlKey = event.ctrlKey || event.metaKey;

    if (usesControlKey && event.shiftKey && event.code === 'KeyZ') {
        event.preventDefault();
        runTileEditorCommand('redo');
        return;
    }

    if (usesControlKey && event.code === 'KeyZ') {
        event.preventDefault();
        runTileEditorCommand('undo');
        return;
    }

    if (!isTileEditingOn.value || !rectangleSelection.value) {
        return;
    }

    if (event.code === 'Enter' || event.code === 'KeyF') {
        event.preventDefault();
        runTileEditorCommand('fill-selection');
    } else if (event.code === 'Delete' || event.code === 'Backspace') {
        event.preventDefault();
        runTileEditorCommand('erase-selection');
    } else if (event.code === 'Escape') {
        event.preventDefault();
        runTileEditorCommand('clear-selection');
    }
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

// Add NPC to group
const addNpcToGroup = (targetNpc: NpcWithLocationResource) => {
    if (!sourceNpc.value || !addToGroupMode.value) return;

    // Check if NPCs are close enough (max 5 tiles in x or y direction)
    const dx = Math.abs(targetNpc.location.x - sourceNpc.value.location.x);
    const dy = Math.abs(targetNpc.location.y - sourceNpc.value.location.y);

    if (dx > 5 || dy > 5) {
        toast.add({ severity: 'error', summary: 'Za daleko', detail: 'NPC jest za daleko (max 5 kratek)', life: 3000 });
        return;
    }

    // If source NPC has a group, add target NPC to that group
    if (sourceNpc.value.group_id) {
        router.post(route('npcs.group.add'), {
            source_npc_id: sourceNpc.value.id,
            target_npc_id: targetNpc.id
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Sukces', detail: 'NPC został dodany do grupy', life: 3000 });
                addToGroupMode.value = false;
                sourceNpc.value = null;
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się dodać NPC do grupy', life: 3000 });
            }
        });
    } else {
        // If source NPC doesn't have a group, create a new group with both NPCs
        router.post(route('npcs.group.create'), {
            npc_ids: [sourceNpc.value.id, targetNpc.id]
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Sukces', detail: 'Utworzono nową grupę z wybranymi NPC', life: 3000 });
                addToGroupMode.value = false;
                sourceNpc.value = null;
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się utworzyć grupy', life: 3000 });
            }
        });
    }
};

// Public methods exposed to parent component
defineExpose({
    setEditColsOn: (value: boolean) => {
        editColsOn.value = value;
        if (value) {
            editWaterOn.value = false;
        }
        finishTileMouseAction();
        emitTileEditorState();
    },
    setEditWaterOn: (value: boolean) => {
        editWaterOn.value = value;
        if (value) {
            editColsOn.value = false;
        }
        finishTileMouseAction();
        emitTileEditorState();
    },
    setTileEditorSettings: (settings: Partial<TileEditorSettings>) => {
        tileEditorSettings.value = {
            ...tileEditorSettings.value,
            ...settings,
            brushSize: clampInteger(settings.brushSize ?? tileEditorSettings.value.brushSize, 1, 12),
            waterDepth: clampInteger(settings.waterDepth ?? tileEditorSettings.value.waterDepth, 1, 9),
            selectedPresetId: settings.selectedPresetId === undefined
                ? tileEditorSettings.value.selectedPresetId
                : settings.selectedPresetId,
        };
    },
    runTileEditorCommand,
    getTileEditorState: (): TileEditorState => ({
        canUndo: undoStack.value.length > 0,
        canRedo: redoStack.value.length > 0,
        presets: presetOptions.value,
        selectionSummary: selectionSummary.value,
    }),
    setMoveNpcLocationData: (npc: NpcWithLocationResource) => {
        moveDoorLocationData.value = null;
        moveNpcLocationData.value = npc;
        addToGroupMode.value = false;
        sourceNpc.value = null;
    },
    setMoveDoorLocationData: (door: DoorResource) => {
        moveDoorLocationData.value = door;
        moveNpcLocationData.value = null;
        addToGroupMode.value = false;
        sourceNpc.value = null;
    },
    setAddToGroupMode: (npc: NpcWithLocationResource) => {
        moveDoorLocationData.value = null;
        moveNpcLocationData.value = null;
        addToGroupMode.value = true;
        sourceNpc.value = npc;
        toast.add({ severity: 'info', summary: 'Tryb grupowania', detail: 'Kliknij na innego NPC w pobliżu (max 5 kratek) aby dodać go do grupy', life: 5000 });
    }
});

watch(() => props.map.id, () => {
    undoStack.value = [];
    redoStack.value = [];
    activeHistorySnapshot.value = null;
    clearRectangleSelection();
});

watch([
    () => undoStack.value.length,
    () => redoStack.value.length,
    () => selectionSummary.value,
    () => tileEditorPresets.value.length,
], () => {
    emitTileEditorState();
});

onMounted(() => {
    loadPresets();
    window.addEventListener('keydown', handleTileEditorKeydown);
    emitTileEditorState();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleTileEditorKeydown);
});

const handleRenewableItemClick = (item) => {
    confirm.require({
        message: `Czy na pewno chcesz usunąć przedmiot: ${item.item.name} (ID: ${item.item.id}) z mapy?`,
        header: 'Usuń przedmiot z mapy',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Usuń',
        rejectLabel: 'Anuluj',
        accept: () => {
            // Tutaj wykonujemy zapytanie usuwające
            router.delete(route('maps.renewable-items.destroy', {map: item.map_id, renewableItem: item.id}), {
                preserveScroll: true,
            });
        },
    });
};
</script>

<template>
    <div class="card overflow-auto m-2" v-if="isMapVisible">
        <div
            ref="mapElementRef"
            class="map-container relative"
            :style="{
                backgroundImage: `url(${map.src})`,
                width: `${map.x * 32 * scale}px`,
                height: `${map.y * 32 * scale}px`,
                transformOrigin: 'top left',
                transform: `translate(${mapOffset.x}px, ${mapOffset.y}px)`,
            }"
            @mousemove="handleMapMouseMove"
            @mousedown="handleMapMouseDown"
            @mouseup="handleMapMouseUp"
            @mouseleave="handleMapMouseLeave"
            @contextmenu.prevent
            @click.capture="handleMapClickCapture"
            @click.self="handleMapClick"
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

            <div
                v-if="selectionOverlayStyle"
                class="tile-selection-overlay"
                :class="{
                    'tile-selection-overlay-water': activeTileLayer === 'water',
                }"
                :style="selectionOverlayStyle"
            />

            <div
                v-for="tile in tilePreviewPositions"
                :key="`preview-${tile.x}-${tile.y}`"
                class="tile-preview"
                :class="{
                    'tile-preview-water': activeTileLayer === 'water',
                    'tile-preview-collision': activeTileLayer === 'cols',
                }"
                :style="{
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    top: `${tile.y * 32 * scale}px`,
                    left: `${tile.x * 32 * scale}px`,
                }"
            >
                <span v-if="tile.depth" class="tile-preview-depth">{{ tile.depth }}</span>
            </div>

            <!-- NPCs -->
            <NpcRenderer
                :npcs="npcs"
                :scale="scale"
                :npc-scale="npcScale"
                :natural-npc-size="naturalNpcSize"
                :add-to-group-mode="addToGroupMode"
                :source-npc="sourceNpc"
                :npc-draw-offset-overrides="npcDrawOffsetOverrides"
                @show-npc-confirm-dialog="(event, npc) => emit('showNpcConfirmDialog', event, npc)"
                @add-to-group="addNpcToGroup"
            />

            <!-- Doors -->
            <DoorRenderer
                :doors="doors"
                :scale="scale"
                @show-door-confirm-dialog="(event, door) => emit('showDoorConfirmDialog', event, door)"
            />

            <!-- RenewableMapItems -->
            <div
                v-for="item in renewableItems"
                :key="`renewable-${item.id}`"
                class="absolute cursor-pointer"
                v-tip.item="item.item"
                :style="{
                    top: `${item.y * 32 * scale}px`,
                    left: `${item.x * 32 * scale}px`,
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    pointerEvents: 'auto',
                    zIndex: 2,
                }"
                @click="handleRenewableItemClick(item)"
            >
                <img
                    :src="item.item.src"
                    :alt="item.item.name"
                    :style="{ width: `${32 * scale}px`, height: `${32 * scale}px`, borderRadius: '6px', border: '2px solid #2196f3', background: '#eff8ff' }"
                />
            </div>

            <!-- Collisions -->
            <CollisionRenderer
                :map="map"
                :scale="scale"
                :edit-cols-on="editColsOn"
            />

            <!-- Water -->
            <WaterRenderer
                :map="map"
                :scale="scale"
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
    z-index: 5;
}

.tile-selection-overlay {
    position: absolute;
    pointer-events: none;
    z-index: 8;
    border: 2px solid #f59e0b;
    background: rgba(245, 158, 11, 0.16);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.85);
}

.tile-selection-overlay-water {
    border-color: #38bdf8;
    background: rgba(56, 189, 248, 0.18);
}

.tile-preview {
    position: absolute;
    pointer-events: none;
    z-index: 9;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.9);
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.25);
}

.tile-preview-collision {
    background: rgba(236, 72, 153, 0.45);
}

.tile-preview-water {
    background: rgba(14, 165, 233, 0.42);
}

.tile-preview-depth {
    color: white;
    font-size: 11px;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
}
</style>
