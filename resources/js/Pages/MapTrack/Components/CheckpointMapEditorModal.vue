<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import { useForm } from '@inertiajs/vue3';
import { useConfirm, useToast } from 'primevue';
import Dialog from 'primevue/dialog';
import AutoComplete from 'primevue/autocomplete';
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';
import CollisionRenderer from '@/Pages/Map/Components/CollisionRenderer.vue';
import { MapResource } from '@/Resources/Map.resource';
import {
    MapTrackCheckpointResource,
    MapTrackCheckpointTileResource,
} from '@/Resources/MapTrack.resource';

type EditorTool = 'line' | 'rectangle' | 'brush' | 'erase';
type Tile = MapTrackCheckpointTileResource;

const props = defineProps<{
    visible: boolean;
    trackId: number;
    checkpoint: MapTrackCheckpointResource | null;
    checkpoints: MapTrackCheckpointResource[];
}>();

const emit = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>();

const confirm = useConfirm();
const toast = useToast();
const selectedMap = ref<MapResource | null>(null);
const mapQuery = ref<MapResource | string | null>(null);
const mapSuggestions = ref<MapResource[]>([]);
const selectedTiles = ref<Set<string>>(new Set());
const previewTiles = ref<Tile[]>([]);
const drawingStart = ref<Tile | null>(null);
const isDrawing = ref(false);
const tool = ref<EditorTool>('line');
const brushSize = ref(1);
const scale = ref(0.75);
const hoveredTile = ref<Tile | null>(null);
const mapCanvas = ref<HTMLElement | null>(null);

const tools = [
    { label: 'Linia', value: 'line', icon: 'pi pi-minus' },
    { label: 'Prostokąt', value: 'rectangle', icon: 'pi pi-stop' },
    { label: 'Pędzel', value: 'brush', icon: 'pi pi-pencil' },
    { label: 'Gumka', value: 'erase', icon: 'pi pi-eraser' },
];

const form = useForm<{
    name: string;
    map_id: number | null;
    tiles: Tile[];
}>({
    name: '',
    map_id: null,
    tiles: [],
});

const tileKey = (tile: Tile): string => `${tile.x}:${tile.y}`;

const tileFromKey = (key: string): Tile => {
    const [x, y] = key.split(':').map(Number);

    return { x, y };
};

const isInsideMap = (tile: Tile): boolean => {
    return Boolean(selectedMap.value)
        && tile.x >= 0
        && tile.y >= 0
        && tile.x < selectedMap.value!.x
        && tile.y < selectedMap.value!.y;
};

const isCollision = (tile: Tile): boolean => {
    if (!selectedMap.value || !isInsideMap(tile)) {
        return true;
    }

    return selectedMap.value.col[tile.y * selectedMap.value.x + tile.x] === '1';
};

const selectedTileList = computed<Tile[]>(() => {
    return [...selectedTiles.value].map(tileFromKey).sort((left, right) => left.y - right.y || left.x - right.x);
});

const otherCheckpointTiles = computed(() => {
    if (!selectedMap.value) {
        return [];
    }

    return props.checkpoints
        .filter((checkpoint) => checkpoint.id !== props.checkpoint?.id && checkpoint.map.id === selectedMap.value?.id)
        .flatMap((checkpoint) => checkpoint.tiles.map((tile) => ({
            ...tile,
            checkpointSequence: checkpoint.sequence,
        })));
});

const checkpointTitle = computed(() => {
    return props.checkpoint ? `Edycja checkpointu ${props.checkpoint.sequence}` : 'Nowy checkpoint';
});

const initialize = () => {
    form.clearErrors();
    form.name = props.checkpoint?.name ?? '';
    form.map_id = props.checkpoint?.map.id ?? null;
    selectedMap.value = props.checkpoint?.map ?? null;
    mapQuery.value = props.checkpoint?.map ?? null;
    selectedTiles.value = new Set((props.checkpoint?.tiles ?? []).map(tileKey));
    previewTiles.value = [];
    drawingStart.value = null;
    isDrawing.value = false;
    hoveredTile.value = null;
    tool.value = 'line';
    scale.value = 0.75;
};

watch(() => props.visible, (visible) => {
    if (visible) {
        initialize();
    }
});

const searchMaps = async (event: any) => {
    const query = String(event?.query ?? event?.[0]?.query ?? '').trim();
    const response = await axios.get(route('maps.search', { search: query }));
    mapSuggestions.value = Array.isArray(response.data) ? response.data : (response.data.data ?? []);
};

const applyMap = (map: MapResource) => {
    selectedMap.value = map;
    mapQuery.value = map;
    form.map_id = map.id;
    selectedTiles.value = new Set();
    previewTiles.value = [];
};

const selectMap = (event: { value: MapResource }) => {
    const map = event.value;
    if (selectedMap.value?.id === map.id) {
        mapQuery.value = map;

        return;
    }

    if (!selectedMap.value || selectedTiles.value.size === 0) {
        applyMap(map);

        return;
    }

    const previousMap = selectedMap.value;
    confirm.require({
        header: 'Zmiana mapy checkpointu',
        message: 'Zmiana mapy usunie aktualnie zaznaczone kratki. Kontynuować?',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Zostań na mapie', severity: 'secondary' },
        acceptProps: { label: 'Zmień mapę', severity: 'warn' },
        reject: () => {
            mapQuery.value = previousMap;
        },
        accept: () => applyMap(map),
    });
};

const pointerTile = (event: PointerEvent): Tile | null => {
    if (!mapCanvas.value || !selectedMap.value) {
        return null;
    }

    const rectangle = mapCanvas.value.getBoundingClientRect();
    const size = 32 * scale.value;
    const tile = {
        x: Math.floor((event.clientX - rectangle.left) / size),
        y: Math.floor((event.clientY - rectangle.top) / size),
    };

    return isInsideMap(tile) ? tile : null;
};

const lineTiles = (start: Tile, end: Tile): Tile[] => {
    const tiles: Tile[] = [];
    let x = start.x;
    let y = start.y;
    const deltaX = Math.abs(end.x - start.x);
    const stepX = start.x < end.x ? 1 : -1;
    const deltaY = -Math.abs(end.y - start.y);
    const stepY = start.y < end.y ? 1 : -1;
    let error = deltaX + deltaY;

    while (true) {
        tiles.push({ x, y });
        if (x === end.x && y === end.y) {
            break;
        }

        const doubledError = 2 * error;
        if (doubledError >= deltaY) {
            error += deltaY;
            x += stepX;
        }
        if (doubledError <= deltaX) {
            error += deltaX;
            y += stepY;
        }
    }

    return tiles;
};

const rectangleTiles = (start: Tile, end: Tile): Tile[] => {
    const tiles: Tile[] = [];
    for (let y = Math.min(start.y, end.y); y <= Math.max(start.y, end.y); y += 1) {
        for (let x = Math.min(start.x, end.x); x <= Math.max(start.x, end.x); x += 1) {
            tiles.push({ x, y });
        }
    }

    return tiles;
};

const brushTiles = (center: Tile): Tile[] => {
    const tiles: Tile[] = [];
    const size = Math.max(1, Math.min(10, Number(brushSize.value ?? 1)));
    const startOffset = -Math.floor((size - 1) / 2);

    for (let offsetY = startOffset; offsetY < startOffset + size; offsetY += 1) {
        for (let offsetX = startOffset; offsetX < startOffset + size; offsetX += 1) {
            tiles.push({ x: center.x + offsetX, y: center.y + offsetY });
        }
    }

    return tiles;
};

const validTiles = (tiles: Tile[]): Tile[] => {
    return tiles.filter((tile) => isInsideMap(tile) && !isCollision(tile));
};

const addTiles = (tiles: Tile[]) => {
    const next = new Set(selectedTiles.value);
    validTiles(tiles).forEach((tile) => next.add(tileKey(tile)));
    selectedTiles.value = next;
};

const removeTiles = (tiles: Tile[]) => {
    const next = new Set(selectedTiles.value);
    tiles.forEach((tile) => next.delete(tileKey(tile)));
    selectedTiles.value = next;
};

const updateShapePreview = (tile: Tile) => {
    if (!drawingStart.value) {
        return;
    }

    previewTiles.value = validTiles(
        tool.value === 'line'
            ? lineTiles(drawingStart.value, tile)
            : rectangleTiles(drawingStart.value, tile),
    );
};

const startDrawing = (event: PointerEvent) => {
    if (event.button !== 0) {
        return;
    }

    const tile = pointerTile(event);
    if (!tile) {
        return;
    }

    event.preventDefault();
    mapCanvas.value?.setPointerCapture(event.pointerId);
    hoveredTile.value = tile;
    drawingStart.value = tile;
    isDrawing.value = true;

    if (tool.value === 'brush') {
        addTiles(brushTiles(tile));
    } else if (tool.value === 'erase') {
        removeTiles(brushTiles(tile));
    } else {
        updateShapePreview(tile);
    }
};

const continueDrawing = (event: PointerEvent) => {
    const tile = pointerTile(event);
    hoveredTile.value = tile;
    if (!tile || !isDrawing.value) {
        return;
    }

    if (tool.value === 'brush') {
        addTiles(brushTiles(tile));
    } else if (tool.value === 'erase') {
        removeTiles(brushTiles(tile));
    } else {
        updateShapePreview(tile);
    }
};

const finishDrawing = (event: PointerEvent) => {
    if (!isDrawing.value) {
        return;
    }

    if (tool.value === 'line' || tool.value === 'rectangle') {
        addTiles(previewTiles.value);
    }

    if (mapCanvas.value?.hasPointerCapture(event.pointerId)) {
        mapCanvas.value.releasePointerCapture(event.pointerId);
    }
    previewTiles.value = [];
    drawingStart.value = null;
    isDrawing.value = false;
};

const clearTiles = () => {
    selectedTiles.value = new Set();
    previewTiles.value = [];
};

const changeScale = (delta: number) => {
    scale.value = Math.max(0.35, Math.min(1.5, Math.round((scale.value + delta) * 100) / 100));
};

const tileStyle = (tile: Tile) => ({
    left: `${tile.x * 32 * scale.value}px`,
    top: `${tile.y * 32 * scale.value}px`,
    width: `${32 * scale.value}px`,
    height: `${32 * scale.value}px`,
});

const close = () => emit('update:visible', false);

const save = () => {
    form.map_id = selectedMap.value?.id ?? null;
    form.tiles = selectedTileList.value;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Checkpoint zapisany', life: 3000 });
            close();
        },
    };

    if (props.checkpoint) {
        form.patch(route('map-tracks.checkpoints.update', {
            mapTrack: props.trackId,
            checkpoint: props.checkpoint.id,
        }), options);

        return;
    }

    form.post(route('map-tracks.checkpoints.store', { mapTrack: props.trackId }), options);
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        maximizable
        :header="checkpointTitle"
        :style="{ width: '96vw' }"
        :content-style="{ height: '80vh' }"
        @update:visible="emit('update:visible', $event)"
    >
        <div class="flex h-full min-h-0 flex-col gap-4">
            <div class="grid shrink-0 grid-cols-1 gap-4 xl:grid-cols-[minmax(220px,0.8fr)_minmax(320px,1.3fr)_auto]">
                <div class="flex flex-col gap-2">
                    <label for="checkpoint-name" class="font-medium">Nazwa checkpointu</label>
                    <InputText id="checkpoint-name" v-model="form.name" placeholder="Np. Most, Meta" />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="checkpoint-map" class="font-medium">Mapa</label>
                    <AutoComplete
                        id="checkpoint-map"
                        v-model="mapQuery"
                        :suggestions="mapSuggestions"
                        option-label="name"
                        placeholder="Wpisz nazwę mapy..."
                        fluid
                        @complete="searchMaps"
                        @item-select="selectMap"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center gap-3">
                                <span class="rounded bg-surface-100 px-2 py-1 text-xs dark:bg-surface-800">#{{ option.id }}</span>
                                <span>{{ option.name }}</span>
                                <span class="text-xs text-surface-500">{{ option.x }} × {{ option.y }}</span>
                            </div>
                        </template>
                    </AutoComplete>
                </div>

                <div class="flex items-end gap-2">
                    <Button icon="pi pi-search-minus" severity="secondary" outlined @click="changeScale(-0.1)" />
                    <span class="min-w-16 pb-2 text-center text-sm font-medium">{{ Math.round(scale * 100) }}%</span>
                    <Button icon="pi pi-search-plus" severity="secondary" outlined @click="changeScale(0.1)" />
                </div>
            </div>

            <div v-if="selectedMap" class="flex shrink-0 flex-wrap items-end gap-4 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Narzędzie</span>
                    <SelectButton v-model="tool" :options="tools" option-label="label" option-value="value">
                        <template #option="{ option }">
                            <span class="flex items-center gap-2"><i :class="option.icon" />{{ option.label }}</span>
                        </template>
                    </SelectButton>
                </div>

                <div v-if="tool === 'brush' || tool === 'erase'" class="flex flex-col gap-2">
                    <label for="checkpoint-brush-size" class="text-sm font-medium">Rozmiar pędzla</label>
                    <InputNumber id="checkpoint-brush-size" v-model="brushSize" :min="1" :max="10" show-buttons class="w-32" />
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <Tag :value="`${selectedTiles.size} kratek`" severity="info" />
                    <span v-if="hoveredTile" class="text-sm text-surface-500">X: {{ hoveredTile.x }}, Y: {{ hoveredTile.y }}</span>
                    <Button label="Wyczyść" icon="pi pi-trash" severity="secondary" outlined :disabled="selectedTiles.size === 0" @click="clearTiles" />
                </div>
            </div>

            <Message v-if="form.errors.map_id" severity="error" class="shrink-0">{{ form.errors.map_id }}</Message>
            <Message v-if="form.errors.tiles" severity="error" class="shrink-0">{{ form.errors.tiles }}</Message>

            <div v-if="selectedMap" class="min-h-0 flex-1 overflow-auto rounded-lg border-2 border-surface-300 bg-surface-900 p-4 dark:border-surface-700">
                <div
                    ref="mapCanvas"
                    class="checkpoint-map-canvas relative select-none overflow-hidden shadow-2xl"
                    :style="{
                        width: `${selectedMap.x * 32 * scale}px`,
                        height: `${selectedMap.y * 32 * scale}px`,
                        backgroundImage: `url(${selectedMap.src})`,
                        backgroundSize: '100% 100%',
                        '--grid-size': `${32 * scale}px`,
                    }"
                    @pointerdown="startDrawing"
                    @pointermove="continueDrawing"
                    @pointerup="finishDrawing"
                    @pointercancel="finishDrawing"
                    @pointerleave="hoveredTile = null"
                >
                    <CollisionRenderer :map="selectedMap" :scale="scale" :edit-cols-on="false" />

                    <div
                        v-for="tile in otherCheckpointTiles"
                        :key="`other-${tile.checkpointSequence}-${tile.x}-${tile.y}`"
                        class="pointer-events-none absolute z-10 flex items-center justify-center border border-amber-300 bg-amber-400/45 text-xs font-bold text-white"
                        :style="tileStyle(tile)"
                    >
                        {{ tile.checkpointSequence }}
                    </div>

                    <div
                        v-for="tile in selectedTileList"
                        :key="`selected-${tile.x}-${tile.y}`"
                        class="pointer-events-none absolute z-20 border border-emerald-200 bg-emerald-500/65"
                        :style="tileStyle(tile)"
                    />

                    <div
                        v-for="tile in previewTiles"
                        :key="`preview-${tile.x}-${tile.y}`"
                        class="pointer-events-none absolute z-30 border border-sky-100 bg-sky-400/70"
                        :class="{ 'opacity-40': selectedTiles.has(tileKey(tile)) }"
                        :style="tileStyle(tile)"
                    />
                </div>
            </div>

            <div v-else class="flex min-h-0 flex-1 items-center justify-center rounded-lg border-2 border-dashed border-surface-300 dark:border-surface-700">
                <div class="max-w-md text-center text-surface-500">
                    <i class="pi pi-map mb-4 text-5xl" />
                    <h4>Wybierz mapę</h4>
                    <p>Po wybraniu mapy zobaczysz jej grafikę i narzędzia do zaznaczenia całej bramki checkpointu.</p>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex w-full items-center justify-between gap-3">
                <span class="text-sm text-surface-500">
                    Zaznaczaj wyłącznie przechodnie kratki. Pola kolizyjne są pomijane automatycznie.
                </span>
                <div class="flex gap-3">
                    <Button label="Anuluj" severity="secondary" outlined @click="close" />
                    <Button
                        label="Zapisz checkpoint"
                        icon="pi pi-check"
                        :loading="form.processing"
                        :disabled="!selectedMap || selectedTiles.size === 0"
                        @click="save"
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.checkpoint-map-canvas {
    cursor: crosshair;
    touch-action: none;
    background-repeat: no-repeat;
}

.checkpoint-map-canvas::after {
    position: absolute;
    inset: 0;
    z-index: 5;
    pointer-events: none;
    content: '';
    background-image:
        linear-gradient(to right, rgb(255 255 255 / 12%) 1px, transparent 1px),
        linear-gradient(to bottom, rgb(255 255 255 / 12%) 1px, transparent 1px);
    background-size: var(--grid-size) var(--grid-size);
}
</style>
