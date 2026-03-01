<script setup lang="ts">
import { BaseEdge, EdgeLabelRenderer, getBezierPath, useVueFlow } from '@vue-flow/core';
import { computed } from 'vue';

const props = defineProps<{
    id: string
    sourceX: number
    sourceY: number
    targetX: number
    targetY: number
    sourcePosition: string
    targetPosition: string
    markerEnd?: string
    data?: {
        dialog_id?: number
    }
    selected?: boolean
}>();

const { findEdge, removeEdges } = useVueFlow();

const edgePath = computed(() => getBezierPath({
    sourceX: props.sourceX,
    sourceY: props.sourceY,
    targetX: props.targetX,
    targetY: props.targetY,
    sourcePosition: props.sourcePosition as any,
    targetPosition: props.targetPosition as any,
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
        :id="id"
        :path="edgePath[0]"
        :marker-end="markerEnd"
        :style="{ strokeWidth: '2px', stroke: selected ? '#4338ca' : '#6366f1' }"
        :interaction-width="28"
    />

    <EdgeLabelRenderer>
        <div
            class="edge-label-renderer__custom nodrag nopan"
            :style="{
                transform: `translate(-50%, -50%) translate(${edgePath[1]}px, ${edgePath[2]}px)`,
            }"
        >
            <button
                type="button"
                class="edge-delete-button"
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
}

.edge-delete-button:hover {
    background: #fee2e2;
    border-color: #f87171;
}
</style>
