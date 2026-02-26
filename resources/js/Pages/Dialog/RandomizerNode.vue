<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import axios from 'axios';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import { DialogNodeOptionEdgeWithRules } from '@/Resources/DialogOption.resource';
import { DialogNodeRulesResource } from '@/Resources/DialogNodeRules.resource';
import { DialogNodeOptionRule } from '@/types/DialogNodeOptionRule';
import RemoveNodeButton from './Componnts/RemoveNodeButton.vue';

const props = defineProps<NodeProps<{
    dialog_id: number
    edges?: DialogNodeOptionEdgeWithRules[]
}>>();

const toast = useToast();
const { edges, removeEdges, updateNodeData } = useVueFlow();

const localEdges = ref<DialogNodeOptionEdgeWithRules[]>([]);
const chances = ref<number[]>([]);
const processing = ref(false);

const clampPercent = (value: number): number => {
    return Math.max(0, Math.min(100, Math.round(value)));
};

const syncFromProps = (): void => {
    localEdges.value = (props.data.edges ?? []).map((edge) => ({
        ...edge,
        rules: edge.rules || {}
    }));

    if (localEdges.value.length === 0) {
        chances.value = [];
        return;
    }

    if (localEdges.value.length === 1) {
        chances.value = [100];
        return;
    }

    const nextChances = localEdges.value.map((edge) => {
        const rawValue = edge.rules?.[DialogNodeOptionRule.percentageChance]?.value;
        return typeof rawValue === 'number' ? clampPercent(rawValue) : 0;
    });

    const editableCount = localEdges.value.length - 1;
    let assigned = 0;

    for (let i = 0; i < editableCount; i++) {
        const maxAllowed = 100 - assigned;
        const normalized = Math.min(nextChances[i], maxAllowed);
        nextChances[i] = normalized;
        assigned += normalized;
    }

    nextChances[localEdges.value.length - 1] = 100 - assigned;
    chances.value = nextChances;
};

watch(() => props.data.edges, syncFromProps, { deep: true, immediate: true });

const handleHasConnections = computed(() => {
    return edges.value.some((edge) => edge.source === props.id);
});

const lastIndex = computed(() => localEdges.value.length - 1);

const maxFor = (index: number): number => {
    if (index >= lastIndex.value) {
        return chances.value[index] ?? 0;
    }

    const assignedWithoutCurrent = chances.value
        .slice(0, lastIndex.value)
        .reduce((sum, chance, idx) => sum + (idx === index ? 0 : chance), 0);

    return Math.max(0, 100 - assignedWithoutCurrent);
};

const refreshLastChance = (): void => {
    if (localEdges.value.length <= 1) {
        chances.value = localEdges.value.length === 1 ? [100] : [];
        return;
    }

    const assigned = chances.value
        .slice(0, lastIndex.value)
        .reduce((sum, chance) => sum + clampPercent(chance), 0);

    chances.value[lastIndex.value] = Math.max(0, 100 - assigned);
};

const updateChance = (index: number, value: number | number[]): void => {
    if (Array.isArray(value) || index >= lastIndex.value) {
        return;
    }

    chances.value[index] = clampPercent(Math.min(value, maxFor(index)));
    refreshLastChance();
};

const removeSourceConnections = (): void => {
    const foundEdges = edges.value.filter((edge) => edge.source === props.id);
    removeEdges(foundEdges);
};

const payloadEdges = computed<DialogNodeOptionEdgeWithRules[]>(() => {
    return localEdges.value.map((edge, index) => {
        const rules: DialogNodeRulesResource = {
            ...(edge.rules || {}),
            [DialogNodeOptionRule.percentageChance]: {
                value: clampPercent(chances.value[index] ?? 0),
                consume: false,
                value2: null,
            }
        };

        return {
            ...edge,
            rules,
        };
    });
});

const save = (): void => {
    processing.value = true;

    axios.patch(route('dialogs.nodes.start-edges.update', {
        dialog: props.data.dialog_id,
        dialogNode: props.id,
    }), {
        edges: payloadEdges.value,
    })
        .then(() => {
            updateNodeData(props.id, {
                edges: payloadEdges.value,
            });
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Szanse losowania zostały zapisane', life: 3000 });
        })
        .catch(({ response }) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response?.data?.message || 'Nie udało się zapisać zmian', life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        });
};
</script>

<template>
    <div class="vue-flow__node-default">
        <div class="node-drag-handle">
            <i class="pi pi-bars" />
            <span>Przeciągnij</span>
        </div>

        <Handle class="dialog-input" type="target" :position="Position.Left" />

        <div class="flex items-center gap-2 mb-3 mt-2 nodrag">
            <span class="text-lg font-bold grow">Losowanie</span>
            <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />
        </div>

        <div class="text-sm text-surface-600 mb-3 nodrag">
            Ustaw szansę dla każdego wyjścia. Ostatnie wyjście zawsze dostaje resztę do 100%.
        </div>

        <Message v-if="localEdges.length === 0" severity="warn" class="nodrag">Brak podłączonych wyjść</Message>

        <div v-else class="flex flex-col gap-3 nodrag">
            <div
                v-for="(edge, index) in localEdges"
                :key="edge.edge_id"
                class="edge-row"
            >
                <div class="flex items-center justify-between gap-3 mb-2">
                    <span class="edge-title">{{ edge.node?.content || `Node #${edge.node?.id}` }}</span>
                    <Tag :value="`${chances[index] ?? 0}%`" :severity="index === lastIndex ? 'warn' : 'info'" />
                </div>

                <Slider
                    :model-value="chances[index] ?? 0"
                    :max="maxFor(index)"
                    :step="1"
                    :disabled="index === lastIndex"
                    class="w-full"
                    @update:model-value="(value) => updateChance(index, value)"
                />

                <small v-if="index === lastIndex" class="text-surface-500">
                    Wyliczane automatycznie jako dopełnienie do 100%
                </small>
            </div>

            <Button :loading="processing" @click="save" size="small" icon="pi pi-save" label="Zapisz szanse" />
        </div>

        <Handle
            v-tooltip.top="'Drag to connect to dialog, right click to remove connections'"
            id="source-source-1"
            class="dialog-output"
            type="source"
            :position="Position.Right"
            @contextmenu.prevent="removeSourceConnections"
        />

        <div v-if="!handleHasConnections" class="text-xs text-orange-600 mt-3">Brak aktywnych połączeń</div>
    </div>
</template>

<style scoped>
.vue-flow__node-default {
    @apply bg-amber-100 min-w-96 min-h-56 text-left p-4;
}

.node-drag-handle {
    @apply flex items-center gap-2 text-xs uppercase tracking-wide text-amber-900 bg-amber-200 px-3 py-2 rounded-md;
    cursor: grab;
    user-select: none;
}

.node-drag-handle:active {
    cursor: grabbing;
}

.dialog-input {
    @apply bg-red-400;
}

.dialog-output {
    @apply bg-blue-400 right-0;
}

.edge-row {
    @apply bg-white rounded-lg p-3 border border-amber-200;
}

.edge-title {
    @apply text-sm font-medium text-surface-800 truncate;
    max-width: 240px;
}
</style>
