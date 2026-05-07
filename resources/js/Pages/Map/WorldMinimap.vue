<script setup lang="ts">
import {computed, onMounted, onUnmounted, ref} from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import axios from 'axios';
import {router} from '@inertiajs/vue3';

interface ApiMap {
    id: number;
    name: string;
    x: number;
    y: number;
    pvp: 'NONE' | 'CONSENT' | 'ALLOWED' | string;
    thumbnail_src?: string | null;
}

interface WorldMinimapNode {
    id: number;
    map_id: number;
    x: number;
    y: number;
    map: ApiMap;
}

interface WorldMinimapData {
    nodes: WorldMinimapNode[];
}

interface MapSearchResult {
    id: number;
    name: string;
}

const props = defineProps<WorldMinimapData>();

const nodes = ref<WorldMinimapNode[]>(props.nodes || []);
const selectedNodeMapId = ref<number | null>(null);

const isDraggingCanvas = ref(false);
const isDraggingNode = ref(false);
const dragNodeId = ref<number | null>(null);
const nodeDragOffset = ref({x: 0, y: 0});

const startX = ref(0);
const startY = ref(0);
const translateX = ref(0);
const translateY = ref(0);

const showGenerateModal = ref(false);

const mapSearchQuery = ref('');
const mapSearchLoading = ref(false);
const mapSearchResults = ref<MapSearchResult[]>([]);
const selectedMapToAddId = ref<number | null>(null);

const viewBoxWidth = 1600;
const viewBoxHeight = 1000;
const TILE_SCALE = 0.7;
const zoom = ref(1);
const MIN_ZOOM = 0.4;
const MAX_ZOOM = 2.5;

const svgTranslate = computed(() => `translate(${translateX.value},${translateY.value})`);
const svgScale = computed(() => `scale(${zoom.value})`);

const selectedNode = computed(() => nodes.value.find((node) => node.map_id === selectedNodeMapId.value) || null);

function nodeWidth(node: WorldMinimapNode) {
    return Math.max(20, Math.min(120, node.map.x * TILE_SCALE));
}

function nodeHeight(node: WorldMinimapNode) {
    return Math.max(20, Math.min(120, node.map.y * TILE_SCALE));
}

function renderX(node: WorldMinimapNode) {
    return node.x * TILE_SCALE;
}

function renderY(node: WorldMinimapNode) {
    return node.y * TILE_SCALE;
}

function borderColor(pvp: string) {
    if (pvp === 'ALLOWED') return '#dc2626';
    if (pvp === 'CONSENT') return '#d97706';
    return '#6b7280';
}

function handleCanvasMouseDown(event: MouseEvent) {
    if ((event.target as Element).closest('.node-group')) {
        return;
    }

    isDraggingCanvas.value = true;
    startX.value = event.clientX - translateX.value;
    startY.value = event.clientY - translateY.value;
    document.body.style.cursor = 'grabbing';
}

function handleNodeMouseDown(event: MouseEvent, node: WorldMinimapNode) {
    event.preventDefault();
    event.stopPropagation();

    selectedNodeMapId.value = node.map_id;
    isDraggingNode.value = true;
    dragNodeId.value = node.id;

    const pointX = (event.clientX - translateX.value) / zoom.value;
    const pointY = (event.clientY - translateY.value) / zoom.value;

    nodeDragOffset.value = {
        x: pointX - renderX(node),
        y: pointY - renderY(node),
    };
}

async function handleMouseUp() {
    const wasDraggingNode = isDraggingNode.value;
    const releasedNodeId = dragNodeId.value;

    isDraggingCanvas.value = false;
    isDraggingNode.value = false;
    dragNodeId.value = null;
    document.body.style.cursor = 'auto';

    if (wasDraggingNode && releasedNodeId) {
        const node = nodes.value.find((n) => n.id === releasedNodeId);
        if (node) {
            void axios.patch(route('web-api.minimap.nodes.update', {node: node.id}), {
                x: Math.round(node.x),
                y: Math.round(node.y),
            });
        }
    }
}

function handleMouseMove(event: MouseEvent) {
    if (isDraggingNode.value && dragNodeId.value) {
        const node = nodes.value.find((n) => n.id === dragNodeId.value);
        if (!node) return;

        node.x = ((event.clientX - translateX.value) / zoom.value - nodeDragOffset.value.x) / TILE_SCALE;
        node.y = ((event.clientY - translateY.value) / zoom.value - nodeDragOffset.value.y) / TILE_SCALE;
        return;
    }

    if (!isDraggingCanvas.value) return;

    translateX.value = event.clientX - startX.value;
    translateY.value = event.clientY - startY.value;
}

function handleMouseLeave() {
    isDraggingCanvas.value = false;
}

function zoomIn() {
    zoom.value = Math.min(MAX_ZOOM, Number((zoom.value + 0.2).toFixed(2)));
}

function zoomOut() {
    zoom.value = Math.max(MIN_ZOOM, Number((zoom.value - 0.2).toFixed(2)));
}

function resetZoom() {
    zoom.value = 1;
}

async function refreshWorldMinimapData() {
    const {data} = await axios.get<WorldMinimapData>(route('web-api.minimap.index'));
    nodes.value = data.nodes || [];

    if (selectedNodeMapId.value && !nodes.value.some((n) => n.map_id === selectedNodeMapId.value)) {
        selectedNodeMapId.value = null;
    }
}

function triggerRegenerate() {
    router.post(route('maps.world.regenerate'));
    showGenerateModal.value = false;
}

async function removeSelectedNode() {
    if (!selectedNode.value) return;
    await axios.delete(route('web-api.minimap.nodes.destroy', {node: selectedNode.value.id}));
    await refreshWorldMinimapData();
}

async function searchMaps() {
    const query = mapSearchQuery.value.trim();
    if (!query) {
        mapSearchResults.value = [];
        return;
    }

    mapSearchLoading.value = true;
    try {
        const {data} = await axios.get(route('maps.search', {search: query}));
        mapSearchResults.value = (data?.data || data || []) as MapSearchResult[];
    } finally {
        mapSearchLoading.value = false;
    }
}

async function addMapToMinimap() {
    if (!selectedMapToAddId.value) return;

    await axios.post(route('web-api.minimap.nodes.store'), {
        map_id: selectedMapToAddId.value,
        near_map_id: selectedNodeMapId.value,
    });

    selectedMapToAddId.value = null;
    mapSearchQuery.value = '';
    mapSearchResults.value = [];

    await refreshWorldMinimapData();
}

onMounted(() => {
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
});

onUnmounted(() => {
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
});
</script>

<template>
    <AppLayout>
        <Card class="mb-4">
            <template #title>
                <div class="flex items-center justify-between gap-3">
                    <div class="font-semibold">Minimapa świata</div>
                    <div class="flex items-center gap-2">
                        <Button label="Odśwież" severity="secondary" @click="refreshWorldMinimapData" />
                        <Button label="Wygeneruj od nowa" severity="warn" @click="showGenerateModal = true" />
                    </div>
                </div>
            </template>

            <template #content>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-9">
                        <div class="world-map-container">
                            <div class="zoom-controls">
                                <Button label="+" size="small" @click="zoomIn" />
                                <Button label="-" size="small" @click="zoomOut" />
                                <Button label="1:1" size="small" severity="secondary" @click="resetZoom" />
                            </div>
                            <svg
                                :viewBox="`0 0 ${viewBoxWidth} ${viewBoxHeight}`"
                                preserveAspectRatio="xMidYMid meet"
                                class="world-map-svg"
                                @mousedown="handleCanvasMouseDown"
                                @mouseleave="handleMouseLeave"
                            >
                                <g :transform="svgTranslate">
                                    <g :transform="svgScale">
                                    <g
                                        v-for="node in nodes"
                                        :key="node.id"
                                        class="node-group"
                                        @mousedown="(event) => handleNodeMouseDown(event, node)"
                                        @click.stop="selectedNodeMapId = node.map_id"
                                    >
                                        <rect
                                            :x="renderX(node)"
                                            :y="renderY(node)"
                                            :width="nodeWidth(node)"
                                            :height="nodeHeight(node)"
                                            :stroke="borderColor(node.map.pvp)"
                                            :class="[
                                                'node-rect',
                                                { 'node-selected': selectedNodeMapId === node.map_id }
                                            ]"
                                        />
                                        <image
                                            v-if="node.map.thumbnail_src"
                                            :href="node.map.thumbnail_src"
                                            :x="renderX(node)"
                                            :y="renderY(node)"
                                            :width="nodeWidth(node)"
                                            :height="nodeHeight(node)"
                                        />
                                        <text
                                            :x="renderX(node) + nodeWidth(node) / 2"
                                            :y="renderY(node) + nodeHeight(node) - 6"
                                            class="node-name"
                                            text-anchor="middle"
                                        >
                                            {{ node.map_id }}
                                        </text>
                                    </g>
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>

                    <div class="col-span-3 flex flex-col gap-4">
                        <Card>
                            <template #title>Zaznaczony węzeł</template>
                            <template #content>
                                <div v-if="selectedNode" class="flex flex-col gap-2 text-sm">
                                    <div><strong>ID mapy:</strong> {{ selectedNode.map_id }}</div>
                                    <div><strong>Nazwa:</strong> {{ selectedNode.map.name }}</div>
                                    <div><strong>Pozycja:</strong> {{ Math.round(selectedNode.x) }}, {{ Math.round(selectedNode.y) }}</div>
                                    <Button label="Usuń z minimapy" severity="danger" @click="removeSelectedNode" />
                                </div>
                                <div v-else class="text-sm text-gray-500">
                                    Kliknij mapkę, aby ją zaznaczyć.
                                </div>
                            </template>
                        </Card>

                        <Card>
                            <template #title>Dodaj mapkę</template>
                            <template #content>
                                <div class="flex flex-col gap-2">
                                    <InputText v-model="mapSearchQuery" placeholder="Szukaj mapy" @input="searchMaps" />
                                    <div v-if="mapSearchLoading" class="text-xs text-gray-500">Szukam...</div>
                                    <div class="max-h-40 overflow-auto border rounded p-1" v-if="mapSearchResults.length">
                                        <button
                                            v-for="result in mapSearchResults"
                                            :key="result.id"
                                            class="w-full text-left px-2 py-1 rounded hover:bg-gray-100"
                                            :class="{ 'bg-blue-100': selectedMapToAddId === result.id }"
                                            @click="selectedMapToAddId = result.id"
                                        >
                                            #{{ result.id }} {{ result.name }}
                                        </button>
                                    </div>
                                    <Button label="Dodaj do minimapy" :disabled="!selectedMapToAddId" @click="addMapToMinimap" />
                                </div>
                            </template>
                        </Card>

                    </div>
                </div>
            </template>
        </Card>

        <Dialog v-model:visible="showGenerateModal" modal header="Potwierdzenie" :style="{ width: '28rem' }">
            <div class="text-sm">
                Czy na pewno chcesz wygenerować minimapę świata od początku?
                Obecny ręczny układ zostanie zastąpiony.
            </div>
            <template #footer>
                <Button label="Anuluj" severity="secondary" @click="showGenerateModal = false" />
                <Button label="Tak, wygeneruj" severity="warn" @click="triggerRegenerate" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.world-map-container {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.world-map-svg {
    width: 100%;
    height: 75vh;
    background: #f8fafc;
    cursor: grab;
}

.zoom-controls {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
    display: flex;
    gap: 6px;
}

.node-rect {
    fill: #334155;
    stroke-width: 2;
}

.node-selected {
    stroke-width: 4;
}

.node-name {
    fill: #f8fafc;
    font-size: 10px;
    font-weight: 700;
    pointer-events: none;
}
</style>
