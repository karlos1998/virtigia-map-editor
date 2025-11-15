<script setup lang="ts">
import {computed, onMounted, ref, onUnmounted} from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import Card from 'primevue/card';

interface ApiMap {
id: number;
name: string;
x: number;
y: number;
pvp: 'NONE' | 'CONSENT' | 'ALLOWED' | number | string;
thumbnailSrc?: string;
thumbnail_src?: string;
}

interface ApiDoor {
id: number;
mapId?: number;
map_id?: number;
x: number;
y: number;
name: string;
minLvl?: number;
min_lvl?: number;
maxLvl?: number;
max_lvl?: number;
goMapId?: number;
go_map_id?: number;
goMapX?: number;
go_x?: number;
goMapY?: number;
go_y?: number;
requiredBaseItemId?: number;
required_base_item_id?: number;
requiredBaseItemName?: string;
required_base_item_name?: string;
}

interface ConnectedMapsData {
maps: ApiMap[];
doors: ApiDoor[];
}

interface GraphNode {
id: number;
map: ApiMap;
x: number;
y: number;
width: number;
height: number;
}

interface MapLayout {
nodes: GraphNode[];
bounds: { width: number; height: number };
}

const TILE_SCALE = 0.7;
const MIN_NODE_WIDTH = 10;
const MIN_NODE_HEIGHT = 10;
const MAX_NODE_WIDTH = 120;
const MAX_NODE_HEIGHT = 120;

const props = defineProps<{ maps: any[]; doors: any[] }>();

const data = ref<ConnectedMapsData | null>(null);
const layout = ref<MapLayout | null>(null);
const hoveredNodeId = ref<number | null>(null);

const isDragging = ref(false);
const startX = ref(0);
const startY = ref(0);
const translateX = ref(0);
const translateY = ref(0);
const svgRef = ref<SVGElement | null>(null);

const svgTranslate = computed(() => `translate(${translateX.value},${translateY.value})`);

function handleMouseDown(event: MouseEvent) {
isDragging.value = true;
startX.value = event.clientX - translateX.value;
startY.value = event.clientY - translateY.value;
document.body.style.cursor = 'grabbing';
}

function handleMouseMove(event: MouseEvent) {
if (!isDragging.value) return;

translateX.value = event.clientX - startX.value;
translateY.value = event.clientY - startY.value;
}

function handleMouseUp() {
isDragging.value = false;
document.body.style.cursor = 'auto';
}

function handleMouseLeave() {
if (isDragging.value) {
isDragging.value = false;
document.body.style.cursor = 'auto';
}
}

onMounted(async () => {
// Use props passed from backend (Inertia) and adapt snake_case -> camelCase fields
try {
const maps: ApiMap[] = (props.maps || []).map((m: any) => ({
id: m.id,
name: m.name,
x: m.x,
y: m.y,
pvp: m.pvp,
thumbnailSrc: m.thumbnail_src ?? m.thumbnailSrc
}));

const doors: ApiDoor[] = (props.doors || []).map((d: any) => ({
id: d.id,
mapId: d.map_id ?? d.mapId,
x: d.x,
y: d.y,
name: d.name,
minLvl: d.min_lvl ?? d.minLvl,
maxLvl: d.max_lvl ?? d.maxLvl,
goMapId: d.go_map_id ?? d.goMapId,
goMapX: d.go_x ?? d.goMapX,
goMapY: d.go_y ?? d.goMapY,
requiredBaseItemId: d.required_base_item_id ?? d.requiredBaseItemId,
requiredBaseItemName: d.required_base_item_name ?? d.requiredBaseItemName,
}));

const result: ConnectedMapsData = { maps, doors };
data.value = result;
layout.value = buildLayout(result);

document.addEventListener('mousemove', handleMouseMove);
document.addEventListener('mouseup', handleMouseUp);
} catch (error) {
console.error('Failed to build connected maps layout:', error);
}
});

onUnmounted(() => {
document.removeEventListener('mousemove', handleMouseMove);
document.removeEventListener('mouseup', handleMouseUp);
});

type PairKey = `${number}-${number}`;
interface PairOffset { a: number; b: number; dx: number; dy: number }
interface Origin { ox: number; oy: number }

function relaxOrigins(
    maps: ApiMap[],
    pairs: PairOffset[],
    origins: Map<number, Origin>,
    iterations = 30,
    step = 0.6
): Map<number, Origin> {
    if (maps.length === 0) return origins;

    const deg = new Map<number, number>();
    for (const m of maps) deg.set(m.id, 0);
    for (const p of pairs) {
        deg.set(p.a, (deg.get(p.a) || 0) + 1);
        deg.set(p.b, (deg.get(p.b) || 0) + 1);
    }
    const anchor = [...deg.entries()].sort((a, b) => b[1] - a[1])[0]?.[0] ?? maps[0].id;

    for (let it = 0; it < iterations; it++) {
        const acc = new Map<number, { dx: number; dy: number; w: number }>();
        for (const m of maps) acc.set(m.id, {dx: 0, dy: 0, w: 0});

        for (const {a, b, dx, dy} of pairs) {
            const oa = origins.get(a)!;
            const ob = origins.get(b)!;

            const ex = (oa.ox + dx) - ob.ox;
            const ey = (oa.oy + dy) - ob.oy;

            const halfX = ex * 0.5;
            const halfY = ey * 0.5;

            const ra = acc.get(a)!;
            ra.dx -= halfX;
            ra.dy -= halfY;
            ra.w += 1;
            const rb = acc.get(b)!;
            rb.dx += halfX;
            rb.dy += halfY;
            rb.w += 1;
        }

        for (const m of maps) {
            if (m.id === anchor) continue;
            const o = origins.get(m.id)!;
            const r = acc.get(m.id)!;
            if (r.w > 0) {
                o.ox += (r.dx / r.w) * step;
                o.oy += (r.dy / r.w) * step;
            }
        }
    }

    let minX = Infinity, minY = Infinity;
    for (const o of origins.values()) {
        if (o.ox < minX) minX = o.ox | 0;
        if (o.oy < minY) minY = o.oy | 0;
    }
    for (const [id, o] of origins) {
        origins.set(id, {ox: o.ox - minX, oy: o.oy - minY});
    }
    return origins;
}

function rectsOverlap(a: GraphNode, b: GraphNode): boolean {
    return !(a.x + a.width <= b.x || b.x + b.width <= a.x || a.y + a.height <= b.y || b.y + b.height <= a.y);
}

function separationVector(a: GraphNode, b: GraphNode): { dxA: number; dyA: number; dxB: number; dyB: number } {
    const overlapX = Math.min(a.x + a.width, b.x + b.width) - Math.max(a.x, b.x);
    const overlapY = Math.min(a.y + a.height, b.y + b.height) - Math.max(a.y, b.y);

    if (overlapX < overlapY) {
        const push = overlapX / 2 + 0.5;
        if (a.x < b.x) return {dxA: -push, dyA: 0, dxB: +push, dyB: 0};
        return {dxA: +push, dyA: 0, dxB: -push, dyB: 0};
    } else {
        const push = overlapY / 2 + 0.5;
        if (a.y < b.y) return {dxA: 0, dyA: -push, dxB: 0, dyB: +push};
        return {dxA: 0, dyA: +push, dxB: 0, dyB: -push};
    }
}

function resolveOverlaps(
    maps: ApiMap[],
    pairs: PairOffset[],
    nodes: GraphNode[],
    tileScale: number,
    maxIters = 10
) {
    for (let it = 0; it < maxIters; it++) {
        let moved = false;

        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const a = nodes[i], b = nodes[j];
                if (!rectsOverlap(a, b)) continue;
                moved = true;
                const sep = separationVector(a, b);
                a.x += sep.dxA;
                a.y += sep.dyA;
                b.x += sep.dxB;
                b.y += sep.dyB;
            }
        }
        if (!moved) break;

        const origins = new Map<number, Origin>();
        for (const n of nodes) {
            origins.set(n.id, {ox: n.x / tileScale, oy: n.y / tileScale});
        }
        const relaxed = relaxOrigins(maps, pairs, origins, 8, 0.5);

        for (const n of nodes) {
            const o = relaxed.get(n.id)!;
            n.x = o.ox * tileScale;
            n.y = o.oy * tileScale;
        }
    }
}

function keyAB(a: number, b: number): PairKey {
    return (a < b ? `${a}-${b}` : `${b}-${a}`) as PairKey;
}

function median(nums: number[]): number {
    if (nums.length === 0) return 0;
    const s = [...nums].sort((x, y) => x - y);
    const m = Math.floor(s.length / 2);
    return s.length % 2 ? s[m] : (s[m - 1] + s[m]) / 2;
}

function buildPairOffsets(doors: ApiDoor[]): PairOffset[] {
    const bucket = new Map<PairKey, { a: number; b: number; dxs: number[]; dys: number[] }>();

    for (const d of doors) {
        const a = Math.min(d.mapId!, d.goMapId!);
        const b = Math.max(d.mapId!, d.goMapId!);
        const key = keyAB(a, b);

        let dxAB: number, dyAB: number;
        if (d.mapId === a && d.goMapId === b) {
            dxAB = d.x - d.goMapX!;
            dyAB = d.y - d.goMapY!;
        } else {
            dxAB = -(d.x - d.goMapX!);
            dyAB = -(d.y - d.goMapY!);
        }

        if (!bucket.has(key)) bucket.set(key, {a, b, dxs: [], dys: []});
        const rec = bucket.get(key)!;
        rec.dxs.push(dxAB);
        rec.dys.push(dyAB);
    }

    const pairs: PairOffset[] = [];
    for (const {a, b, dxs, dys} of bucket.values()) {
        pairs.push({a, b, dx: median(dxs), dy: median(dys)});
    }
    return pairs;
}

function solveWorldOrigins(maps: ApiMap[], pairs: PairOffset[]): Map<number, { ox: number; oy: number }> {
    const adj = new Map<number, PairOffset[]>();
    for (const m of maps) adj.set(m.id, []);
    for (const p of pairs) {
        adj.get(p.a)?.push(p);
        adj.get(p.b)?.push(p);
    }

    const origins = new Map<number, { ox: number; oy: number }>();
    const unplaced = new Set(maps.map(m => m.id));

    let nextCompX = 0;
    const COMP_GAP = 50; // tile

    while (unplaced.size) {
        const start = unplaced.values().next().value as number;
        origins.set(start, {ox: nextCompX, oy: 0});
        unplaced.delete(start);

        const q = [start];
        let compMinX = 0, compMaxX = 0;

        while (q.length) {
            const v = q.shift()!;
            const ov = origins.get(v)!;

            for (const e of adj.get(v) ?? []) {
                const {a, b, dx, dy} = e;
                const u = v === a ? b : a;

                const oux = v === a ? ov.ox + dx : ov.ox - dx;
                const ouy = v === a ? ov.oy + dy : ov.oy - dy;

                if (!origins.has(u)) {
                    origins.set(u, {ox: oux, oy: ouy});
                    unplaced.delete(u);
                    q.push(u);
                    compMinX = Math.min(compMinX, oux);
                    compMaxX = Math.max(compMaxX, oux);
                }
            }
        }

        nextCompX = compMaxX - compMinX + COMP_GAP + nextCompX;
    }

    let minX = Infinity, minY = Infinity;
    for (const o of origins.values()) {
        if (o.ox < minX) minX = o.ox | 0;
        if (o.oy < minY) minY = o.oy | 0;
    }
    for (const [id, o] of origins) {
        origins.set(id, {ox: o.ox - minX, oy: o.oy - minY});
    }

    return origins;
}

function placeComponents(doors: ApiDoor[], maps: ApiMap[]): GraphNode[] {
const pairOffsets = buildPairOffsets(doors);

let origins = solveWorldOrigins(maps, pairOffsets);
origins = relaxOrigins(maps, pairOffsets, origins, 30, 2);

const nodes: GraphNode[] = maps.map(m => {
const o = origins.get(m.id)!;
return {
id: m.id,
map: m,
x: o.ox * TILE_SCALE,
y: o.oy * TILE_SCALE,
width: Math.min(Math.max(m.x * TILE_SCALE, MIN_NODE_WIDTH), MAX_NODE_WIDTH),
height: Math.min(Math.max(m.y * TILE_SCALE, MIN_NODE_HEIGHT), MAX_NODE_HEIGHT),
};
});

resolveOverlaps(maps, pairOffsets, nodes, TILE_SCALE, 40);

return nodes;
}


function buildLayout(data: ConnectedMapsData): MapLayout {
const nodes = placeComponents(data.doors, data.maps);

// Bigger default bounds so map is spacious on page
return {
nodes,
    bounds: {width: 900, height: 600}
};
}

function handleNodeHover(nodeId: number | null) {
hoveredNodeId.value = nodeId;
}

const pvpBorderColor = computed(() => ({
    'NONE': '#6b7280', // gray
    'CONSENT': '#d97706', // amber
    'ALLOWED': '#dc2626' // red
} as Record<string, string>));
</script>

<template>
    <AppLayout>
        <Card class="mb-4">
            <template #title>
                <div class="flex items-center justify-between">
                    <div class="font-semibold">Minimapa świata</div>
                </div>
            </template>
            <template #content>
                <div class="world-map-container">
                    <svg
                        v-if="layout"
                        ref="svgRef"
                        :viewBox="`0 0 ${layout.bounds.width} ${layout.bounds.height}`"
                        preserveAspectRatio="xMidYMid meet"
                        class="world-map-svg"
                        @mousedown="handleMouseDown"
                        @mouseleave="handleMouseLeave"
                    >
                        <!-- Nodes -->
                        <g class="nodes" :transform="svgTranslate">
                            <g
                                v-for="node in layout.nodes"
                                :key="node.id"
                                class="node-group"
                                v-tip="node.map.name"
                                @mouseenter="handleNodeHover(node.id)"
                                @mouseleave="handleNodeHover(null)"
                            >
                                <rect
                                    :x="node.x"
                                    :y="node.y"
                                    :width="node.width"
                                    :height="node.height"
                                    :stroke="pvpBorderColor[node.map.pvp]"
                                    :class="[
                                        'node-rect',
                                        { 'node-hovered': hoveredNodeId === node.id }
                                    ]"
                                />

                                <!-- Thumbnail or initials -->
                                <g v-if="node.map.thumbnailSrc" class="node-thumbnail">
                                    <image
                                        :href="node.map.thumbnailSrc"
                                        :x="node.x"
                                        :y="node.y"
                                        :width="node.width"
                                        :height="node.height"
                                        clip-path="inset(0)"
                                    />
                                </g>
                                <g v-else class="node-initials">
                                    <rect
                                        :x="node.x + TILE_SCALE"
                                        :y="node.y + TILE_SCALE"
                                        :width="node.width - TILE_SCALE * 2"
                                        :height="node.height - TILE_SCALE * 2"
                                        fill="#374151"
                                    />
                                </g>

                                <!-- Map name overlay -->
                                <text
                                    :x="node.x + node.width / 2"
                                    :y="node.y + node.height - 8"
                                    class="node-name"
                                    text-anchor="middle"
                                >
                                    {{ node.map.id }}
                                </text>
                            </g>
                        </g>
                    </svg>

                    <!-- Loading state -->
                    <div v-else class="loading">
                        Loading world map...
                    </div>
                </div>
            </template>
        </Card>
    </AppLayout>
</template>

<style scoped>
.world-map-container {
    position: relative;
    overflow-y: hidden;
    overflow-x: hidden;
    max-width: 100%;
    max-height: 100%;
}

.world-map-svg {
    background-color: transparent;
    border-radius: 8px;
    cursor: grab;
    width: 100%;
}

.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 400px;
    color: #9ca3af;
    font-size: 16px;
}

/* Nodes */
.node-rect {
    fill: #374151;
    stroke-width: 2;
    opacity: 0.9;
}

.node-rect.node-hovered {
    stroke-width: 3;
    opacity: 1;
}

.node-name {
    fill: #f9fafb;
    font-size: 9px;
    font-weight: bold;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
    pointer-events: none;
}
</style>
