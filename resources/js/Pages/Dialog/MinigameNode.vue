<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import axios from 'axios';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import RemoveNodeButton from './Componnts/RemoveNodeButton.vue';
import { DialogNodeOptionEdgeWithRules } from '@/Resources/DialogOption.resource';

type MinigameType = 'pipes' | 'saper' | 'mastermind' | 'random';

const props = defineProps<NodeProps<{
    dialog_id: number;
    action_data?: {
        minigame?: {
            type?: MinigameType;
            difficulty?: number;
        };
    };
    edges?: DialogNodeOptionEdgeWithRules[];
}>>();

const toast = useToast();
const { edges, removeEdges, updateNodeData } = useVueFlow();

const minigameOptions = [
    { label: 'Rurki', value: 'pipes' },
    { label: 'Kryształy', value: 'saper' },
    { label: 'Kolory', value: 'mastermind' },
    { label: 'Losowa', value: 'random' },
];

const difficultyOptions = [
    { label: '1', value: 1 },
    { label: '2', value: 2 },
    { label: '3', value: 3 },
];

const selectedType = ref<MinigameType>(props.data.action_data?.minigame?.type ?? 'pipes');
const selectedDifficulty = ref<number>(props.data.action_data?.minigame?.difficulty ?? 1);
const localEdges = ref<DialogNodeOptionEdgeWithRules[]>(props.data.edges ?? []);
const processing = ref(false);

watch(() => props.data.edges, (value) => {
    localEdges.value = value ?? [];
}, { deep: true });

const successEdge = computed(() => localEdges.value.find((edge) => edge.source_handle === 'source-success'));
const failEdge = computed(() => localEdges.value.find((edge) => edge.source_handle === 'source-fail'));

const hasSuccessConnection = computed(() => {
    return edges.value.some((edge) => edge.source === props.id && edge.sourceHandle === 'source-success');
});

const hasFailConnection = computed(() => {
    return edges.value.some((edge) => edge.source === props.id && edge.sourceHandle === 'source-fail');
});

const save = (): void => {
    processing.value = true;

    axios.patch(route('dialogs.nodes.action.update', {
        dialog: props.data.dialog_id,
        dialogNode: props.id,
    }), {
        minigame: {
            type: selectedType.value,
            difficulty: selectedDifficulty.value,
        },
    })
        .then(({ data }) => {
            updateNodeData(data.dialogNode.id.toString(), data.dialogNode.data);
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Minigra została zapisana', life: 3000 });
        })
        .catch(({ response }) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response?.data?.message || 'Nie udało się zapisać minigry', life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        });
};

const removeSourceConnections = (sourceHandle: string): void => {
    const foundEdges = edges.value.filter((edge) => edge.source === props.id && edge.sourceHandle === sourceHandle);
    removeEdges(foundEdges);
};

const edgeLabel = (edge?: DialogNodeOptionEdgeWithRules): string => {
    if (!edge?.node) {
        return 'Brak połączenia';
    }

    return edge.node.content || `Node #${edge.node.id}`;
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
            <span class="text-lg font-bold grow">Minigra</span>
            <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />
        </div>

        <div class="flex flex-col gap-3 nodrag">
            <label class="field-label">
                <span>Typ</span>
                <Select v-model="selectedType" :options="minigameOptions" option-label="label" option-value="value" class="w-full" />
            </label>

            <label class="field-label">
                <span>Trudność</span>
                <Select v-model="selectedDifficulty" :options="difficultyOptions" option-label="label" option-value="value" class="w-full" />
            </label>

            <Button :loading="processing" size="small" icon="pi pi-save" label="Zapisz" @click="save" />
        </div>

        <div class="outputs nodrag">
            <div class="output-row" :class="{ connected: hasSuccessConnection || successEdge }">
                <div>
                    <strong>Sukces</strong>
                    <span>{{ edgeLabel(successEdge) }}</span>
                </div>
                <Handle
                    id="source-success"
                    v-tooltip.top="'Wyjście po wygranej minigrze'"
                    class="dialog-output success"
                    type="source"
                    :position="Position.Right"
                    @contextmenu.prevent="removeSourceConnections('source-success')"
                />
            </div>

            <div class="output-row" :class="{ connected: hasFailConnection || failEdge }">
                <div>
                    <strong>Porażka</strong>
                    <span>{{ edgeLabel(failEdge) }}</span>
                </div>
                <Handle
                    id="source-fail"
                    v-tooltip.top="'Wyjście po przegranej minigrze'"
                    class="dialog-output fail"
                    type="source"
                    :position="Position.Right"
                    @contextmenu.prevent="removeSourceConnections('source-fail')"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.vue-flow__node-default {
    @apply bg-cyan-100 min-w-96 min-h-56 text-left p-4;
}

.node-drag-handle {
    @apply flex items-center gap-2 text-xs uppercase text-cyan-900 bg-cyan-200 px-3 py-2 rounded-md;
    cursor: grab;
    user-select: none;
}

.node-drag-handle:active {
    cursor: grabbing;
}

.field-label {
    @apply flex flex-col gap-1 text-sm font-medium text-surface-700;
}

.dialog-input {
    @apply bg-red-400;
}

.outputs {
    @apply mt-4 flex flex-col gap-2;
}

.output-row {
    @apply relative flex items-center justify-between rounded-lg border border-cyan-200 bg-white p-3 pr-8 text-sm;
}

.output-row.connected {
    @apply border-emerald-300 bg-emerald-50;
}

.output-row strong {
    @apply block text-surface-800;
}

.output-row span {
    @apply block max-w-64 truncate text-surface-500;
}

.dialog-output {
    @apply right-0;
}

.dialog-output.success {
    @apply bg-emerald-500;
}

.dialog-output.fail {
    @apply bg-red-500;
}
</style>
