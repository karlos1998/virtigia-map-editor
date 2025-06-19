<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { ConnectionLookup, Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useDialog } from 'primevue/usedialog';
import EditDialog from '@/Pages/Dialog/Modals/EditDialog.vue';
import axios from "axios";
import { route } from "ziggy-js";
import { useToast } from "primevue";
import RemoveNodeButton from "./Componnts/RemoveNodeButton.vue";
import { DialogNodeOptionEdgeWithRules } from "@/Resources/DialogOption.resource";
import { DialogNodeRulesResource } from "@/Resources/DialogNodeRules.resource";
import EditOption from '@/Pages/Dialog/Modals/EditOption.vue';
import EditEdges from '@/Pages/Dialog/Modals/EditEdges.vue';
import Button from 'primevue/button';
import Message from 'primevue/message';

const primeDialog = useDialog();
const toast = useToast();
const showEditOption = ref(false);
const showEditEdges = ref(false);
const currentEdge = ref<DialogNodeOptionEdgeWithRules | null>(null);

const props = defineProps<NodeProps<{
    dialog_id: number
    edges?: DialogNodeOptionEdgeWithRules[]
}>>();

const { updateNodeData, edges, removeEdges, connectionLookup } = useVueFlow();

const nodeEdges = ref<DialogNodeOptionEdgeWithRules[]>(props.data.edges || []);

const handleHasConnections = reactive<Record<string, boolean>>({});

watch(connectionLookup, (value: ConnectionLookup) => {
    // Find connections for the source handle
    const sourceConnections = value.get(`${props.id}-source-source-1`);
    handleHasConnections['source-source-1'] = sourceConnections?.size > 0;

    console.log('handleHasConnections', value, handleHasConnections);
}, { deep: true, immediate: true, flush: 'post' });

const removeSourceConnections = () => {
    const foundEdges = edges.value.filter((edge) => edge.source === props.id)
        .filter((edge) => edge.sourceHandle === 'source-source-1');
    console.log('removeSourceConnections', foundEdges);
    removeEdges(foundEdges);
};

const editEdge = (edge: DialogNodeOptionEdgeWithRules) => {
    currentEdge.value = edge;
    showEditOption.value = true;
};

const handleEditOptionClose = (closeData: { remove?: boolean, dialogOption?: any }) => {
    console.log('handleEditOptionClose', closeData);

    if (!currentEdge.value) return;

    if (closeData?.dialogOption) {
        // Update edge rules
        currentEdge.value.rules = closeData.dialogOption.edges[0]?.rules || {};
    }

    updateNodeData(props.id, {
        edges: [...nodeEdges.value]
    });

    // Reset state
    showEditOption.value = false;
    currentEdge.value = null;
};

const handleEditEdgesClose = (closeData: { edges?: DialogNodeOptionEdgeWithRules[] }) => {
    console.log('handleEditEdgesClose', closeData);

    if (closeData?.edges) {
        // Update all edges
        nodeEdges.value = closeData.edges;

        updateNodeData(props.id, {
            edges: [...nodeEdges.value]
        });
    }

    // Reset state
    showEditEdges.value = false;
};

const form = reactive({
    rules: {},
    edges: [],
})

form.edges = nodeEdges.value.map(edge => ({
    ...edge,
    rules: edge.rules || {},
    options: [],
})) || [];
</script>

<template>
    <div class="vue-flow__node-default">
        <div class="font-bold text-lg flex flex-row gap-1">
            <span class="grow">Start</span>
            <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />
        </div>

        <div class="edges mt-4">
            <div class="card">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-xl">Reguły przejścia do kolejnych dialogów</h3>
                    <Button
                        v-if="nodeEdges && nodeEdges.length > 0"
                        icon="pi pi-pencil"
                        @click="showEditEdges = true"
                        severity="primary"
                    />
                </div>
                <Message v-if="!nodeEdges || nodeEdges.length === 0" severity="warn">Brak kolejnych kroków dialogowych</Message>
                <div v-else class="text-sm text-gray-600">
                    Kliknij przycisk "Edytuj reguły", aby zarządzać regułami przejścia do kolejnych dialogów.
                </div>
            </div>
        </div>


        <Handle v-tooltip.top="'Drag to connect to dialog, right click to remove connections'"
                id="source-source-1"
                class="dialog-output" type="source" :position="Position.Right"
                @contextmenu.prevent="removeSourceConnections()" />
    </div>

    <EditEdges
        v-model:visible="showEditEdges"
        @close="handleEditEdgesClose"
        :edges="nodeEdges"
        :parent="props.id"
        :dialog_id="data.dialog_id"
    />
</template>

<style scoped>
.vue-flow__node-default {
    @apply bg-green-200 min-w-96 min-h-56 text-left;
}

.dialog-output {
    @apply bg-blue-400 right-0;
}

.card {
    background-color: var(--surface-card);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 1rem;
}
</style>
