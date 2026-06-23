<script setup lang="ts">
import { BaseEdge, EdgeLabelRenderer, useVueFlow } from '@vue-flow/core';
import { computed } from 'vue';

const props = defineProps<{
    id: string
    source: string
    target: string
    sourceX: number
    sourceY: number
    targetX: number
    targetY: number
    sourcePosition: string
    targetPosition: string
    sourceHandleId?: string | null
    targetHandleId?: string | null
    markerEnd?: string
    data?: {
        dialog_id?: number
    }
    selected?: boolean
}>();

type RoutedEdgePath = {
    path: string
    labelX: number
    labelY: number
};

type EdgeGeometry = {
    id: string
    source: string
    target: string
    sourceX: number
    sourceY: number
    targetX: number
    targetY: number
};

type RouteProfile = {
    source: number
    target: number
    pair: number
    crossingCount: number
    middleXOffset: number
    middleYOffset: number
};

const edgeColors = [
    '#4f46e5',
    '#0891b2',
    '#16a34a',
    '#d97706',
    '#db2777',
    '#7c3aed',
    '#0d9488',
    '#dc2626',
    '#2563eb',
    '#9333ea',
];

const { edges, findEdge, findNode, removeEdges } = useVueFlow();

const clamp = (value: number, min: number, max: number): number => {
    return Math.min(max, Math.max(min, value));
};

const formatCoordinate = (value: number): number => {
    return Number(value.toFixed(2));
};

const stableHash = (value: string): number => {
    return [...value].reduce((hash, character) => {
        return (hash * 31 + character.charCodeAt(0)) % 9973;
    }, 0);
};

const edgeSortKey = (edge: any): string => {
    return [
        edge.source,
        edge.sourceHandle ?? '',
        edge.target,
        edge.targetHandle ?? '',
        edge.id,
    ].join(':');
};

const centeredOffset = (edgeIds: string[], currentEdgeId: string, spacing: number, maxOffset: number): number => {
    if (edgeIds.length <= 1) {
        return 0;
    }

    const index = edgeIds.indexOf(currentEdgeId);

    if (index < 0) {
        return 0;
    }

    return clamp((index - (edgeIds.length - 1) / 2) * spacing, -maxOffset, maxOffset);
};

const getHandle = (handles: any[] = [], handleId?: string | null): any | null => {
    if (!handles.length) {
        return null;
    }

    return handles.find((handle) => handle.id === handleId) ?? handles[0] ?? null;
};

const getHandlePosition = (node: any, handle: any, fallbackPosition: string): { x: number; y: number } => {
    const x = (handle?.x ?? 0) + node.computedPosition.x;
    const y = (handle?.y ?? 0) + node.computedPosition.y;
    const width = handle?.width ?? node.dimensions.width;
    const height = handle?.height ?? node.dimensions.height;

    switch (handle?.position ?? fallbackPosition) {
        case 'top':
            return { x: x + width / 2, y };
        case 'right':
            return { x: x + width, y: y + height / 2 };
        case 'bottom':
            return { x: x + width / 2, y: y + height };
        default:
            return { x, y: y + height / 2 };
    }
};

const resolveEdgeGeometry = (edge: any): EdgeGeometry | null => {
    if (edge.id === props.id) {
        return {
            id: props.id,
            source: props.source,
            target: props.target,
            sourceX: props.sourceX,
            sourceY: props.sourceY,
            targetX: props.targetX,
            targetY: props.targetY,
        };
    }

    const sourceNode = findNode(edge.source);
    const targetNode = findNode(edge.target);

    if (!sourceNode || !targetNode) {
        return null;
    }

    const sourceHandle = getHandle(sourceNode.handleBounds?.source, edge.sourceHandle);
    const targetHandle = getHandle(targetNode.handleBounds?.target, edge.targetHandle);
    const source = getHandlePosition(sourceNode, sourceHandle, 'right');
    const target = getHandlePosition(targetNode, targetHandle, 'left');

    return {
        id: edge.id,
        source: edge.source,
        target: edge.target,
        sourceX: source.x,
        sourceY: source.y,
        targetX: target.x,
        targetY: target.y,
    };
};

const edgeRank = (edgeIds: string[], edgeId: string): number => {
    return Math.max(0, edgeIds.indexOf(edgeId));
};

const routeProfile = computed<RouteProfile>(() => {
    const currentGeometry = resolveEdgeGeometry({
        id: props.id,
        source: props.source,
        target: props.target,
        sourceHandle: props.sourceHandleId,
        targetHandle: props.targetHandleId,
    });

    if (!currentGeometry) {
        return {
            source: 0,
            target: 0,
            pair: 0,
            crossingCount: 0,
            middleXOffset: 0,
            middleYOffset: 0,
        };
    }

    const currentDistanceX = currentGeometry.targetX - currentGeometry.sourceX;
    const currentMiddleX = currentGeometry.sourceX + currentDistanceX / 2;

    const transitionGeometries = edges.value
        .map(resolveEdgeGeometry)
        .filter((edge): edge is EdgeGeometry => {
            if (!edge) {
                return false;
            }

            const distanceX = edge.targetX - edge.sourceX;
            const middleX = edge.sourceX + distanceX / 2;

            return currentDistanceX > 0
                && distanceX > 0
                && Math.abs(middleX - currentMiddleX) <= Math.max(320, Math.abs(currentDistanceX) * 0.7);
        });

    const sourceEdges = transitionGeometries
        .filter((edge) => edge.source === props.source)
        .sort((a, b) => a.sourceY - b.sourceY || a.targetY - b.targetY || edgeSortKey(a).localeCompare(edgeSortKey(b)))
        .map((edge) => edge.id);

    const targetEdges = transitionGeometries
        .filter((edge) => edge.target === props.target)
        .sort((a, b) => a.targetY - b.targetY || a.sourceY - b.sourceY || edgeSortKey(a).localeCompare(edgeSortKey(b)))
        .map((edge) => edge.id);

    const pairEdges = transitionGeometries
        .filter((edge) => edge.source === props.source && edge.target === props.target)
        .sort((a, b) => a.sourceY - b.sourceY || edgeSortKey(a).localeCompare(edgeSortKey(b)))
        .map((edge) => edge.id);

    const sourceOrder = [...transitionGeometries]
        .sort((a, b) => a.sourceY - b.sourceY || a.targetY - b.targetY || edgeSortKey(a).localeCompare(edgeSortKey(b)))
        .map((edge) => edge.id);

    const targetOrder = [...transitionGeometries]
        .sort((a, b) => a.targetY - b.targetY || a.sourceY - b.sourceY || edgeSortKey(a).localeCompare(edgeSortKey(b)))
        .map((edge) => edge.id);

    const sourceRank = edgeRank(sourceOrder, props.id);
    const targetRank = edgeRank(targetOrder, props.id);
    const crossingCount = transitionGeometries.filter((edge) => {
        if (edge.id === props.id) {
            return false;
        }

        return (edge.sourceY - currentGeometry.sourceY) * (edge.targetY - currentGeometry.targetY) < 0;
    }).length;

    const hashNudge = (stableHash([
        props.id,
        props.source,
        props.sourceHandleId ?? '',
        props.target,
        props.targetHandleId ?? '',
    ].join(':')) % 5 - 2) * 6;

    const source = centeredOffset(sourceEdges, props.id, 18, 90);
    const target = centeredOffset(targetEdges, props.id, 16, 80);
    const pair = centeredOffset(pairEdges, props.id, 24, 96);
    const inversionOffset = (sourceRank - targetRank) * 34;
    const crossingSpread = Math.min(360, 70 + crossingCount * 22 + transitionGeometries.length * 3);

    return {
        source,
        target,
        pair,
        crossingCount,
        middleXOffset: clamp((sourceRank - targetRank) * 7 + hashNudge, -110, 110),
        middleYOffset: clamp(inversionOffset + source * 0.35 + target * 0.35 + pair * 0.55 + hashNudge * 2, -crossingSpread, crossingSpread),
    };
});

const edgePath = computed<RoutedEdgePath>(() => {
    const sourceX = props.sourceX;
    const sourceY = props.sourceY;
    const targetX = props.targetX;
    const targetY = props.targetY;
    const distanceX = targetX - sourceX;

    if (distanceX <= 0) {
        const distance = Math.abs(distanceX);
        const controlSpread = Math.max(96, Math.min(260, distance * 0.35));
        const clearance = 112 + Math.min(220, Math.max(distance, 120) * 0.16) + Math.abs(routeProfile.value.middleYOffset) * 0.28;
        const direction = sourceY >= targetY ? -1 : 1;
        const arcY = direction === -1
            ? Math.min(sourceY, targetY) - clearance
            : Math.max(sourceY, targetY) + clearance;
        const middleX = sourceX + distanceX / 2;

        return {
            path: [
                `M ${formatCoordinate(sourceX)} ${formatCoordinate(sourceY)}`,
                `C ${formatCoordinate(sourceX + controlSpread)} ${formatCoordinate(sourceY)}`,
                `${formatCoordinate(sourceX + controlSpread)} ${formatCoordinate(arcY)}`,
                `${formatCoordinate(middleX)} ${formatCoordinate(arcY)}`,
                `C ${formatCoordinate(targetX - controlSpread)} ${formatCoordinate(arcY)}`,
                `${formatCoordinate(targetX - controlSpread)} ${formatCoordinate(targetY)}`,
                `${formatCoordinate(targetX)} ${formatCoordinate(targetY)}`,
            ].join(' '),
            labelX: middleX,
            labelY: arcY,
        };
    }

    const controlDistance = Math.max(120, Math.min(340, distanceX * 0.42));
    const sourceFanOffset = clamp(routeProfile.value.source + routeProfile.value.pair * 0.35 + routeProfile.value.middleYOffset * 0.12, -130, 130);
    const targetFanOffset = clamp(routeProfile.value.target - routeProfile.value.pair * 0.35 - routeProfile.value.middleYOffset * 0.12, -130, 130);
    const middleX = sourceX + distanceX / 2 + routeProfile.value.middleXOffset;
    const middleY = sourceY + (targetY - sourceY) / 2 + routeProfile.value.middleYOffset;
    const middleControlDistance = Math.max(80, Math.min(180, distanceX * 0.22));

    return {
        path: [
            `M ${formatCoordinate(sourceX)} ${formatCoordinate(sourceY)}`,
            `C ${formatCoordinate(sourceX + controlDistance)} ${formatCoordinate(sourceY + sourceFanOffset)}`,
            `${formatCoordinate(middleX - middleControlDistance)} ${formatCoordinate(middleY)}`,
            `${formatCoordinate(middleX)} ${formatCoordinate(middleY)}`,
            `C ${formatCoordinate(middleX + middleControlDistance)} ${formatCoordinate(middleY)}`,
            `${formatCoordinate(targetX - controlDistance)} ${formatCoordinate(targetY + targetFanOffset)}`,
            `${formatCoordinate(targetX)} ${formatCoordinate(targetY)}`,
        ].join(' '),
        labelX: middleX,
        labelY: middleY,
    };
});

const edgeColor = computed(() => {
    const colorIndex = stableHash(`${props.source}:${props.target}`) % edgeColors.length;

    return edgeColors[colorIndex];
});

const edgeStyle = computed(() => ({
    strokeWidth: props.selected ? '3.25px' : (routeProfile.value.crossingCount > 0 ? '2.1px' : '1.8px'),
    stroke: edgeColor.value,
    strokeOpacity: props.selected ? 0.96 : (routeProfile.value.crossingCount > 0 ? 0.66 : 0.5),
    strokeLinecap: 'round',
}));

const edgeHaloStyle = computed(() => ({
    strokeWidth: props.selected ? '7px' : '5px',
    stroke: '#ffffff',
    strokeOpacity: props.selected ? 0.95 : 0.78,
    strokeLinecap: 'round',
}));

const deleteEdge = (): void => {
    const edge = findEdge(props.id);
    if (edge) {
        removeEdges([edge]);
    }
};
</script>

<template>
    <BaseEdge
        :id="`${id}-halo`"
        :path="edgePath.path"
        :style="edgeHaloStyle"
        :interaction-width="0"
    />

    <BaseEdge
        :id="id"
        :path="edgePath.path"
        :marker-end="markerEnd"
        :style="edgeStyle"
        :interaction-width="28"
    />

    <EdgeLabelRenderer>
        <div
            class="edge-label-renderer__custom nodrag nopan"
            :class="{ 'is-visible': selected }"
            :style="{
                transform: `translate(-50%, -50%) translate(${edgePath.labelX}px, ${edgePath.labelY}px)`,
            }"
        >
            <button
                type="button"
                class="edge-delete-button"
                aria-label="Usuń połączenie"
                @click.stop="deleteEdge"
            >
                ×
            </button>
        </div>
    </EdgeLabelRenderer>
</template>

<style scoped>
.edge-label-renderer__custom {
    position: absolute;
    pointer-events: all;
    opacity: 0;
    transition: opacity 0.12s ease;
}

.edge-label-renderer__custom:hover,
.edge-label-renderer__custom.is-visible {
    opacity: 1;
}

.edge-delete-button {
    width: 20px;
    height: 20px;
    border-radius: 9999px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #dc2626;
    font-size: 14px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.18);
    cursor: pointer;
}

.edge-delete-button:hover {
    background: #fee2e2;
    border-color: #f87171;
}
</style>
