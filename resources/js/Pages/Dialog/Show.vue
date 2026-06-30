<script setup lang="ts">
import AppLayout from '../../layout/AppLayout.vue';

import { MiniMap } from '@vue-flow/minimap';
import { ConnectionMode, EdgeRemoveChange, NodeProps, useVueFlow, VueFlow } from '@vue-flow/core';

import SpecialNode from '@/Pages/Dialog/SpecialNode.vue';
import { Controls } from '@vue-flow/controls';
import StartNode from '@/Pages/Dialog/StartNode.vue';
import ShopNode from '@/Pages/Dialog/ShopNode.vue';
import HotelNode from '@/Pages/Dialog/HotelNode.vue';
import RandomizerNode from '@/Pages/Dialog/RandomizerNode.vue';
import ProfessionNode from '@/Pages/Dialog/ProfessionNode.vue';
import MinigameNode from '@/Pages/Dialog/MinigameNode.vue';
import DialogEdge from '@/Pages/Dialog/DialogEdge.vue';
import DialogActivityLogsTable from '@/Pages/Dialog/Partials/DialogActivityLogsTable.vue';
import { DialogResource } from '@/Resources/Dialog.resource';
import { NpcWithLocationsResource } from '@/Resources/Npc.resource';
import { SimpleQuestResource } from '@/Resources/Quest.resource';
import type { AdvanceTableResponse } from '@/karlos3098-LaravelPrimevueTable/Services/tableService';
// import { DialogConnectionResource } from '@/Resources/DialogConnection.resource';
import { computed, nextTick, ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import TeleporationNode from '@/Pages/Dialog/TeleporationNode.vue';
import { useToast } from 'primevue';
import EditDialogNameDialog from '@/Pages/Dialog/Modals/EditDialogNameDialog.vue';
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import { Link, useForm } from '@inertiajs/vue3';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

type DialogNodeLayoutPositions = Record<string, {
    x: number;
    y: number;
}>;

const props = defineProps<{
    dialog: DialogResource,
    nodes: any[], //todo
    edges: any[],
    npcs: NpcWithLocationsResource[],
    focusNpcs: NpcWithLocationsResource[],
    quests: SimpleQuestResource[],
    logs: AdvanceTableResponse<any>,
    dialogNodeOptionAdditionalActionsList: any[],
    availableRules: any[],
    dialogNodeAdditionalActionsList: any[],
}>();

const isEditDialogNameVisible = ref(false);
const isAddNodeFromJsonVisible = ref(false);
const nodeFromJsonProcessing = ref(false);
const isLayoutProcessing = ref(false);
const nodeFromJsonInput = ref('');
const isJsonDocumentationVisible = ref(false);
const addNodeFromJsonModalContentRef = ref<HTMLElement | null>(null);
const confirm = useConfirm();
const toast = useToast();

// Form for copying the dialog
const copyForm = useForm({});

// Function to copy the dialog
const copyDialog = () => {
    confirm.require({
        group: 'dialog-show-modal',
        message: 'Czy na pewno chcesz skopiować ten dialog? Utworzy to nowy dialog z nazwą "' + props.dialog.name + ' - kopia"',
        header: 'Potwierdzenie',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-primary',
        accept: () => {
            copyForm.post(route('dialogs.copy', { dialog: props.dialog.id }));
        }
    });
};

const {
    edges,
    nodes,
    onConnect,
    addEdges,
    addNodes,
    viewport,
    screenToFlowCoordinate,
    onNodeDragStop,
    onEdgesChange,
    applyEdgeChanges,
    onNodesChange,
    applyNodeChanges,
    updateNodeData,
    setNodes,
    fitView,
} = useVueFlow();

const applyLayoutPositions = (positions: DialogNodeLayoutPositions): void => {
    setNodes(nodes.value.map((node) => {
        const position = positions[node.id.toString()];

        if (!position) {
            return node;
        }

        return {
            ...node,
            position: {
                x: position.x,
                y: position.y,
            },
        };
    }));
};

const executeLayoutNodes = async (): Promise<void> => {
    isLayoutProcessing.value = true;

    try {
        const { data } = await axios.post<{ positions: DialogNodeLayoutPositions }>(
            route('dialogs.layout-nodes', { dialog: props.dialog.id })
        );

        applyLayoutPositions(data.positions ?? {});

        await nextTick();
        await fitView({ padding: 0.2 });

        toast.add({ severity: 'success', summary: 'Gotowe', detail: 'Nody zostały poukładane', life: 3000 });
    } catch ({ response }: any) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: response?.data?.message || 'Nie udało się poukładać nodów', life: 6000 });
    } finally {
        isLayoutProcessing.value = false;
    }
};

const layoutNodes = (): void => {
    confirm.require({
        group: 'dialog-show-modal',
        message: 'Poukładać automatycznie pozycje nodów w tym dialogu? Obecne pozycje zostaną nadpisane.',
        header: 'Potwierdzenie',
        icon: 'pi pi-sitemap',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Poukładaj',
            severity: 'primary'
        },
        accept: executeLayoutNodes,
    });
};

onConnect(({ source, target, sourceHandle, targetHandle }) => {
    addEdges([
        {
            id: `${source}->${target}`,
            source,
            target,
            sourceHandle,
            targetHandle
        }
    ]);
});

function nodeStroke(n: NodeProps) {
    switch (n.type) {
        case 'special':
            return '#213e5e';
        case 'start':
            return '#6bc965';
        case 'randomizer':
            return '#f59e0b';
        case 'shop':
            return '#7a1a10';
        case 'hotel':
            return '#1f4f64';
        case 'profession':
            return '#2563eb';
        case 'minigame':
            return '#0891b2';
        default:
            return '#999';
    }
}

function nodeColor(n: NodeProps) {
    switch (n.type) {
        case 'special':
            return '#112e4e';
        case 'start':
            return '#6bc965';
        case 'randomizer':
            return '#f59e0b';
        case 'shop':
            return '#7a1a10';
        case 'hotel':
            return '#1f4f64';
        case 'profession':
            return '#2563eb';
        case 'minigame':
            return '#67e8f9';
        default:
            return '#888';
    }
}

const isDirectOutputNode = (type?: string): boolean => {
    return type === 'start' || type === 'randomizer' || type === 'minigame';
};

const addNode = (type?: string, position?: { x: number; y: number }) => {
    axios.post(route('dialogs.nodes.store', { dialog: props.dialog.id }), {
        position: position ?? {
            x: -viewport.value.x,
            y: -viewport.value.y
        },
        type
    }).then(({ data: { node } }) => {
        addNodes([node]);
    })
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        });
};

const draggableNodeTemplates = [
    { label: 'Special', type: undefined, icon: 'pi pi-circle' },
    { label: 'Shop', type: 'shop', icon: 'pi pi-shopping-cart' },
    { label: 'Hotel', type: 'hotel', icon: 'pi pi-home' },
    { label: 'Teleport', type: 'teleportation', icon: 'pi pi-forward' },
    { label: 'Profesje', type: 'profession', icon: 'pi pi-users' },
    { label: 'Losowanie', type: 'randomizer', icon: 'pi pi-percentage' },
    { label: 'Minigra', type: 'minigame', icon: 'pi pi-th-large' },
];

const handleNodeTemplateDragStart = (event: DragEvent, type?: string): void => {
    if (!event.dataTransfer) {
        return;
    }

    event.dataTransfer.setData('application/x-dialog-node-type', type ?? 'special');
    event.dataTransfer.effectAllowed = 'copy';
};

const handleFlowDragOver = (event: DragEvent): void => {
    if (!event.dataTransfer) {
        return;
    }

    if (event.dataTransfer.types.includes('application/x-dialog-node-type')) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    }
};

const handleFlowDrop = (event: DragEvent): void => {
    if (!event.dataTransfer) {
        return;
    }

    const droppedType = event.dataTransfer.getData('application/x-dialog-node-type');
    if (!droppedType) {
        return;
    }

    event.preventDefault();

    const flowPosition = screenToFlowCoordinate({
        x: event.clientX,
        y: event.clientY,
    });

    addNode(droppedType === 'special' ? undefined : droppedType, {
        x: flowPosition.x,
        y: flowPosition.y,
    });
};

const defaultNodeFromJsonSample = `{
  "node": {
    "type": "special",
    "position": {
      "x": 220,
      "y": 120
    },
    "content": "Witaj podróżniku. Czego potrzebujesz?"
  },
  "options": [
    {
      "label": "Pokaż mi sklep",
      "rules": {
        "gold": {
          "operator": ">=",
          "value": 100
        }
      }
    },
    {
      "label": "Kończę rozmowę"
    }
  ]
}`;

const advancedNodeFromJsonSamples = [
    {
        label: 'Opcja z wymaganym przedmiotem',
        payload: `{
  "node": {
    "type": "special",
    "position": {
      "x": 250,
      "y": 130
    },
    "content": "Masz coś dla mnie?"
  },
  "options": [
    {
      "label": "Tak, mam wymagany item",
      "rules": {
        "items": {
          "operator": ">=",
          "value": {
            "1001": 1
          }
        }
      }
    },
    {
      "label": "Jeszcze nie."
    }
  ]
}`
    },
    {
        label: 'Opcja z akcją po kliknięciu',
        payload: `{
  "node": {
    "type": "special",
    "position": {
      "x": 260,
      "y": 150
    },
    "content": "Mogę cię uleczyć."
  },
  "options": [
    {
      "label": "Ulecz mnie",
      "additional_action": "HEAL",
      "additional_actions": {
        "addGold": {
          "value": 100
        }
      },
      "cooldown": 5
    },
    {
      "label": "Nie teraz"
    }
  ]
}`
    },
    {
        label: 'Node z additional_actions',
        payload: `{
  "node": {
    "type": "special",
    "position": {
      "x": 280,
      "y": 170
    },
    "content": "Dostaniesz bonus doświadczenia.",
    "action_data": {
      "focus": {
        "type": "coordinates",
        "x": 12,
        "y": 34
      }
    },
    "additional_actions": {
      "addExpPercent": {
        "value": 12.5
      }
    }
  },
  "options": [
    {
      "label": "Dziękuję!"
    }
  ]
}`
    }
];

nodeFromJsonInput.value = defaultNodeFromJsonSample;

const allNodeJsonSamples = [
    {
        label: 'Minimalny special',
        payload: defaultNodeFromJsonSample
    },
    ...advancedNodeFromJsonSamples,
    {
        label: 'Special z questem i cooldownem',
        payload: `{
  "node": {
    "type": "special",
    "position": {
      "x": 320,
      "y": 180
    },
    "content": "Czy chcesz przyjąć zadanie?"
  },
  "options": [
    {
      "label": "Tak, biorę.",
      "cooldown": 3,
      "rules": {
        "questBeforeStep": {
          "operator": ">=",
          "value": "q-123"
        }
      }
    },
    {
      "label": "Nie teraz."
    }
  ]
}`
    },
    {
        label: 'Teleportation node',
        payload: `{
  "node": {
    "type": "teleportation",
    "position": {
      "x": 360,
      "y": 220
    },
    "content": "Teleport do miasta."
  }
}`
    },
    {
        label: 'Randomizer node',
        payload: `{
  "node": {
    "type": "randomizer",
    "position": {
      "x": 390,
      "y": 240
    },
    "content": "Losowanie odpowiedzi."
  }
}`
    },
    {
        label: 'Minigame node',
        payload: `{
  "node": {
    "type": "minigame",
    "position": {
      "x": 420,
      "y": 260
    },
    "action_data": {
      "minigame": {
        "type": "pipes",
        "difficulty": 1
      }
    }
  }
}`
    }
];

const addNodeFromJson = async () => {
    let parsedPayload;

    try {
        parsedPayload = JSON.parse(nodeFromJsonInput.value);
    } catch (_error) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nieprawidłowy JSON.', life: 6000 });

        return;
    }

    nodeFromJsonProcessing.value = true;

    try {
        const { data: { node } } = await axios.post(route('dialogs.nodes.import-json', { dialog: props.dialog.id }), parsedPayload);
        addNodes([node]);
        isAddNodeFromJsonVisible.value = false;
    } catch ({ response }: any) {
        const validationErrors = response?.data?.errors
            ? Object.values(response.data.errors).flat().join('\n')
            : null;
        const message = validationErrors || response?.data?.message || 'Nie udało się dodać noda z JSON.';

        toast.add({ severity: 'error', summary: 'Błąd', detail: message, life: 9000 });
    } finally {
        nodeFromJsonProcessing.value = false;
    }
};

const selectNodeJsonSample = async (payload: string): Promise<void> => {
    nodeFromJsonInput.value = payload;

    await nextTick();
    addNodeFromJsonModalContentRef.value?.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
};

onNodeDragStop(({ node }) => {
    axios.post(route('dialogs.nodes.move', {
        dialog: props.dialog.id,
        dialogNode: node.id
    }), {
        position: node.position
    }).catch((error) => {
        //todo - toast ze nie udalo sie przeniesc
    });
});

const startNodes = computed(() => props.nodes);
const startEdges = computed(() => props.edges);


onNodesChange(async (changes) => {
    const nextChanges = [];

    console.log('onNodesChange', changes);

    for (const change of changes) {
        if (change.type === 'remove') {
            if (nodes.value.find((node) => node.id.toString() === change.id && node.type === 'start')) {
                //...
            } else {
                // confirmDeleteNode(change);
            }
        } else if (change.type === 'select' && change.selected) {
            // console.log('selected node: ' + change.id)
        } else {
            nextChanges.push(change);
        }
    }

    applyNodeChanges(nextChanges);
});

onEdgesChange(async (changes) => {
    const nextChanges = [];

    console.log('onEdgesChange', changes);

    for (const change of changes) {
        if (change.type === 'add') {
            axios.post(route('dialogs.edges.store', {
                dialog: props.dialog.id
            }), {
                sourceNodeIsInput: isDirectOutputNode(change.item.sourceNode.type),
                sourceNodeId: change.item.sourceNode.id,
                sourceOptionId: !isDirectOutputNode(change.item.sourceNode.type) ? change.item.sourceHandle.substring(7) : null,
                targetNodeId: change.item.targetNode.id,
                sourceHandle: isDirectOutputNode(change.item.sourceNode.type) ? change.item.sourceHandle : null,
            }).then(({ data: { edge } }) => {
                console.log(' add edge from backend ---<', edge, props.dialog)
                let edgeTmp = change;
                edgeTmp.item.id = edge.id;
                edgeTmp.item.data = edge.data;
                applyEdgeChanges([edgeTmp]);

                const targetNodeId = change.item.targetNode.id;

                // Update the option's edges list
                if (isDirectOutputNode(change.item.sourceNode.type)) {
                    const sourceNodeId = change.item.sourceNode.id;
                    const sourceNode = nodes.value.find((node) => node.id === sourceNodeId);

                    if (sourceNode?.data) {
                        const directEdges = Array.isArray(sourceNode.data.edges) ? [...sourceNode.data.edges] : [];

                        directEdges.push({
                            edge_id: edge.id,
                            source_handle: change.item.sourceHandle ?? null,
                            node: {
                                id: targetNodeId,
                                type: change.item.targetNode.type,
                                content: change.item.targetNode.data?.content || ''
                            },
                            rules: {}
                        });

                        updateNodeData(sourceNodeId, {
                            edges: directEdges
                        });
                    }
                } else if (change.item.sourceHandle) {
                    const sourceNodeId = change.item.sourceNode.id;
                    const sourceOptionId = change.item.sourceHandle.substring(7);

                    // Find the source node
                    const sourceNode = nodes.value.find(node => node.id === sourceNodeId);
                    if (sourceNode && sourceNode.data && sourceNode.data.options) {
                        // Find the option within the node
                        const option = sourceNode.data.options.find(opt => opt.id.toString() === sourceOptionId);
                        if (option) {
                            // Add the edge to the option's edges list if it doesn't already exist
                            if (!option.edges.some(e => e.edge_id === edge.id)) {
                                option.edges.push({
                                    edge_id: edge.id,
                                    node: {
                                        id: targetNodeId,
                                        type: change.item.targetNode.type,
                                        content: change.item.targetNode.data?.content || ''
                                    },
                                    rules: {}
                                });

                                // Update the node data
                                updateNodeData(sourceNodeId, {
                                    options: [...sourceNode.data.options]
                                });
                            }
                        }
                    }
                }
            })
                // .catch((error) => {
                // applyEdgeChanges([{
                //     type: 'remove',
                //     id: change.item.id,
                //     source: change.item.source,
                //     sourceHandle: change.item.sourceHandle,
                //     target: change.item.target,
                //     targetHandle: change.item.targetHandle
                // }]);
            // });
                .catch(({response}) => {
                    toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
                })
        } else if (change.type === 'remove') {

            removeEdge(change);
        } else {
            nextChanges.push(change);
        }
    }

    applyEdgeChanges(nextChanges);
});

const removeEdge = (edgeChange: EdgeRemoveChange) => {
    confirm.require({
        group: 'dialog-show-modal',
        message: 'Usunąć to połączenie między nodeami?',
        header: 'Potwierdzenie',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Usuń',
            severity: 'danger'
        },
        accept: () => {
            executeRemoveEdge(edgeChange);
        }
    });
}

const executeRemoveEdge = (edgeChange: EdgeRemoveChange) => {
    // Find the edge in the edges array
    const edge = edges.value.find(e => e.id === edgeChange.id) ?? props.edges.find(e => e.id === edgeChange.id);
    const directSourceNode = edge?.source ? nodes.value.find((node) => node.id === edge.source && isDirectOutputNode(node.type)) : null;

    if (edge?.source && directSourceNode?.data?.edges) {
        updateNodeData(edge.source, {
            edges: directSourceNode.data.edges.filter((item) => item.edge_id != edgeChange.id)
        });
    } else if (edge && edge.source && edge.sourceHandle) {
        // Extract the source node ID and option ID from the edge
        const sourceNodeId = edge.source;
        const sourceOptionId = edge.sourceHandle.substring(7); // Remove "source-" prefix



        // Find the source node
        const sourceNode = nodes.value.find(node => node.id === sourceNodeId);

        console.log('remove edge', sourceOptionId, sourceNodeId, sourceNode)

        if (sourceNode && sourceNode.data && sourceNode.data.options) {
            // Find the option within the node
            const option = sourceNode.data.options.find(opt => opt.id.toString() === sourceOptionId);

            console.log('found option', option)

            if (option) {
                // Remove the edge from the option's edges list

                console.log('remove edge id... ', edgeChange.id);

                console.log('edges before', option.edges);

                option.edges = option.edges.filter(e => {
                    console.log('e.edge_id !== edgeChange.id', e.edge_id , edgeChange.id)
                    return e.edge_id != edgeChange.id;
                });

                console.log('edges after', option.edges);

                // Update the node data
                updateNodeData(sourceNodeId, {
                    options: [...sourceNode.data.options]
                });
            }
        }
    }

    axios.delete(route('dialogs.edges.destroy', {
        dialog: props.dialog.id,
        dialogEdge: edgeChange.id,
    }))
        .then(() => {
            applyEdgeChanges([edgeChange]);
        })
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
}


// import { DialogRuleResource } from '@/Resources/DialogRule.resource';

const items = ref([
    {
        label: 'Add',
        icon: 'pi pi-credit-card',
        command: () => {
            addNode();
        }
    },
    {
        label: 'Shop',
        icon: 'pi pi-shopping-cart',
        command: () => {
            addNode('shop');
        }
    },
    {
        label: 'Hotel',
        icon: 'pi pi-home',
        command: () => {
            addNode('hotel');
        }
    },
    {
        label: 'Teleport',
        icon: 'pi pi-forward',
        command: () => {
            addNode('teleportation');
        }
    },
    {
        label: 'Profesje',
        icon: 'pi pi-users',
        command: () => {
            addNode('profession');
        }
    },
    {
        label: 'Losowanie',
        icon: 'pi pi-percentage',
        command: () => {
            addNode('randomizer');
        }
    },
    {
        label: 'Minigra',
        icon: 'pi pi-th-large',
        command: () => {
            addNode('minigame');
        }
    },
    {
        label: 'JSON',
        icon: 'pi pi-code',
        command: () => {
            isAddNodeFromJsonVisible.value = true;
        }
    }
]);
</script>

<template>
    <AppLayout focus>
        <ConfirmDialog group="dialog-show-modal" />

        <EditDialogNameDialog :dialog="props.dialog" v-model:visible="isEditDialogNameVisible" />
        <Dialog v-model:visible="isAddNodeFromJsonVisible" modal header="Dodaj node z JSON" :style="{ width: '48rem' }">
            <div ref="addNodeFromJsonModalContentRef" class="flex flex-col gap-3 max-h-[70vh] overflow-y-auto pr-1">
                <p class="text-sm text-surface-500">
                    Wklej JSON z obiektem <strong>node</strong> oraz opcjonalną tablicą <strong>options</strong>.
                </p>
                <Textarea v-model="nodeFromJsonInput" rows="18" class="w-full font-mono text-sm min-h-[22rem] flex-none" />
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-surface-500">Przykłady:</span>
                    <Button
                        v-for="sample in advancedNodeFromJsonSamples"
                        :key="sample.label"
                        :label="sample.label"
                        severity="secondary"
                        size="small"
                        outlined
                        @click="selectNodeJsonSample(sample.payload)"
                    />
                </div>
                <div class="border border-surface-200 dark:border-surface-700 rounded-lg">
                    <button
                        type="button"
                        class="w-full px-4 py-3 flex items-center justify-between text-left font-medium"
                        @click="isJsonDocumentationVisible = !isJsonDocumentationVisible"
                    >
                        <span>Dokumentacja JSON</span>
                        <i class="pi" :class="isJsonDocumentationVisible ? 'pi-chevron-up' : 'pi-chevron-down'" />
                    </button>
                    <div v-if="isJsonDocumentationVisible" class="px-4 pb-4 flex flex-col gap-3 text-sm">
                        <Message severity="info" :closable="false">
                            Dla <strong>special</strong> wymagane jest minimum jedno <strong>options[]</strong>.
                        </Message>
                        <div>
                            <div class="font-semibold mb-1">Schemat</div>
                            <pre class="bg-surface-100 dark:bg-surface-900 p-3 rounded overflow-auto">{
  "node": {
    "type": "special|shop|hotel|teleportation|randomizer|profession|minigame",
    "position": { "x": 220, "y": 120 },
    "content": "opcjonalny tekst",
    "action_data": {
      "focus": { "type": "npc|coordinates|reset", "npcId": 1, "locationId": 1, "mapId": 1, "x": 12, "y": 34 },
      "minigame": { "type": "pipes|saper|mastermind|random", "difficulty": 1 }
    },
    "additional_actions": { "...": { "value": 1 } }
  },
  "options": [
    {
      "label": "Treść opcji",
      "additional_action": "HEAL",
      "additional_actions": { "...": { "value": 1 } },
      "cooldown": 5,
      "rules": { "...": {} }
    }
  ]
}</pre>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div class="font-semibold">Dostępne `node.additional_actions` i `options[].additional_actions`</div>
                            <div class="text-surface-600 dark:text-surface-300">
                                {{ props.dialogNodeAdditionalActionsList.map((item) => item.value).join(', ') || '-' }}
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div class="font-semibold">Dostępne `options[].additional_action`</div>
                            <div class="text-surface-600 dark:text-surface-300">
                                {{ props.dialogNodeOptionAdditionalActionsList.map((item) => item.value).join(', ') || '-' }}
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div class="font-semibold">Dostępne klucze `rules`</div>
                            <div class="text-surface-600 dark:text-surface-300">
                                {{ props.availableRules.map((item) => item.value).join(', ') || '-' }}
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-surface-500">Pełne przykłady:</span>
                            <Button
                                v-for="sample in allNodeJsonSamples"
                                :key="`all-${sample.label}`"
                                :label="sample.label"
                                severity="secondary"
                                size="small"
                                text
                                @click="selectNodeJsonSample(sample.payload)"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <Button label="Anuluj" severity="secondary" @click="isAddNodeFromJsonVisible = false" />
                    <Button label="Dodaj node" :loading="nodeFromJsonProcessing" @click="addNodeFromJson" />
                </div>
            </div>
        </Dialog>

        <Tabs value="tree" class="dialog-tabs">
            <TabList>
                <Tab value="general">
                    <i class="pi pi-info-circle mr-2" />
                    Ogólne
                </Tab>
                <Tab value="tree">
                    <i class="pi pi-sitemap mr-2" />
                    Drzewo
                </Tab>
                <Tab value="relations">
                    <i class="pi pi-link mr-2" />
                    Powiązania
                </Tab>
                <Tab value="history">
                    <i class="pi pi-history mr-2" />
                    Historia
                </Tab>
            </TabList>

            <TabPanels>
                <TabPanel value="tree" class="dialog-tree-panel">
                    <div class="dialog-flow-shell" @dragover="handleFlowDragOver" @drop="handleFlowDrop">
                        <div class="dialog-flow-toolbar">
                            <div class="flex min-w-0 items-center gap-3">
                                <Link :href="route('dialogs.index')">
                                    <Button
                                        icon="pi pi-arrow-left"
                                        label="Powrót"
                                        severity="secondary"
                                        outlined
                                    />
                                </Link>
                                <div class="min-w-0">
                                    <div class="text-sm text-surface-500">Dialog #{{ props.dialog.id }}</div>
                                    <h2 class="m-0 truncate text-xl font-semibold">{{ props.dialog.name }}</h2>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Tag :value="`${startNodes.length} node`" severity="info" />
                                <Tag :value="`${startEdges.length} połączeń`" severity="secondary" />
                                <Button
                                    label="Poukładaj"
                                    icon="pi pi-sitemap"
                                    :loading="isLayoutProcessing"
                                    @click="layoutNodes"
                                />
                                <Button
                                    label="JSON"
                                    icon="pi pi-code"
                                    severity="secondary"
                                    outlined
                                    @click="isAddNodeFromJsonVisible = true"
                                />
                            </div>
                        </div>

                        <VueFlow
                            class="dialog-flow"
                            :nodes="startNodes"
                            :edges="startEdges"
                            :connection-mode="ConnectionMode.Strict"
                            :max-zoom="1"
                            :delete-key-code="'Backspace'"
                            :elements-selectable="true"
                            :edges-focusable="true"
                            fit-view-on-init
                            :apply-default="false"
                            :default-edge-options="{
                                type: 'default',
                                style: { strokeWidth: '2px', stroke: '#6366f1' },
                                animated: false
                            }"
                            :elevate-edges-on-select="true"
                        >
                            <template #node-special="specialNodeProps">
                                <SpecialNode v-bind="specialNodeProps" />
                            </template>
                            <template #node-start="startNodeProps">
                                <StartNode v-bind="startNodeProps" />
                            </template>
                            <template #node-shop="shopNodeProps">
                                <ShopNode v-bind="shopNodeProps" />
                            </template>
                            <template #node-hotel="hotelNodeProps">
                                <HotelNode v-bind="hotelNodeProps" />
                            </template>
                            <template #node-randomizer="randomizerNodeProps">
                                <RandomizerNode v-bind="randomizerNodeProps" />
                            </template>
                            <template #node-profession="professionNodeProps">
                                <ProfessionNode v-bind="professionNodeProps" />
                            </template>
                            <template #node-minigame="minigameNodeProps">
                                <MinigameNode v-bind="minigameNodeProps" />
                            </template>
                            <template #node-teleportation="teleportationNodeProps">
                                <TeleporationNode v-bind="teleportationNodeProps" />
                            </template>

                            <template #edge-default="customEdgeProps">
                                <DialogEdge v-bind="customEdgeProps" />
                            </template>

                            <Controls position="top-left">
                                <SpeedDial :model="items" direction="up" />
                                <div class="node-dnd-palette ml-2">
                                    <div class="node-dnd-title">Node</div>
                                    <button
                                        v-for="template in draggableNodeTemplates"
                                        :key="template.label"
                                        type="button"
                                        draggable="true"
                                        class="node-dnd-item"
                                        @dragstart="handleNodeTemplateDragStart($event, template.type)"
                                    >
                                        <i class="pi" :class="template.icon" />
                                        <span>{{ template.label }}</span>
                                    </button>
                                </div>
                            </Controls>

                            <MiniMap :node-stroke-color="nodeStroke" :node-color="nodeColor" />
                        </VueFlow>
                    </div>
                </TabPanel>

                <TabPanel value="general" class="dialog-scroll-panel">
                    <DetailsCardList title="Ogólne">
                        <DetailsCardListItem label="ID" :value="String(props.dialog.id)" />
                        <DetailsCardListItem label="Nazwa">
                            <template #value>
                                <span class="font-medium">{{ props.dialog.name }}</span>
                            </template>
                        </DetailsCardListItem>
                        <DetailsCardListItem label="Akcje">
                            <template #value>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        label="Edytuj nazwę"
                                        icon="pi pi-pencil"
                                        size="small"
                                        @click="isEditDialogNameVisible = true"
                                    />
                                    <Button
                                        label="Kopiuj dialog"
                                        icon="pi pi-copy"
                                        size="small"
                                        severity="secondary"
                                        :loading="copyForm.processing"
                                        @click="copyDialog"
                                    />
                                </div>
                            </template>
                        </DetailsCardListItem>
                    </DetailsCardList>
                </TabPanel>

                <TabPanel value="relations" class="dialog-scroll-panel">
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        <section class="dialog-section">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h2 class="m-0 text-xl font-semibold">Powiązane NPC</h2>
                                <Tag :value="String(props.npcs.length)" severity="info" />
                            </div>

                            <Message v-if="props.npcs.length === 0" severity="secondary">
                                Ten dialog nie jest jeszcze przypisany do żadnego NPC.
                            </Message>

                            <div v-else class="flex flex-col gap-3">
                                <Link
                                    v-for="npc in props.npcs"
                                    :key="npc.id"
                                    :href="route('npcs.show', { npc: npc.id })"
                                    class="related-card"
                                >
                                    <img :src="npc.src" :alt="npc.name" class="h-12 w-12 rounded-md bg-surface-100 object-cover" />
                                    <div class="min-w-0 grow">
                                        <div class="truncate font-semibold">#{{ npc.id }} - {{ npc.name }}</div>
                                        <div class="text-sm text-surface-500">Poziom {{ npc.lvl }}</div>
                                        <div v-if="npc.locations?.length" class="truncate text-sm text-surface-500">
                                            [{{ npc.locations[0].map_id }}] {{ npc.locations[0].map_name }} ({{ npc.locations[0].x }},{{ npc.locations[0].y }})
                                        </div>
                                        <div v-else class="text-sm text-surface-400">
                                            Brak lokalizacji
                                        </div>
                                    </div>
                                    <Tag :value="npc.enabled ? 'Aktywny' : 'Wyłączony'" :severity="npc.enabled ? 'success' : 'danger'" />
                                </Link>
                            </div>
                        </section>

                        <section class="dialog-section">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h2 class="m-0 text-xl font-semibold">Powiązane Questy</h2>
                                <Tag :value="String(props.quests.length)" severity="contrast" />
                            </div>

                            <Message v-if="props.quests.length === 0" severity="secondary">
                                W tym dialogu nie wykryto powiązań questowych.
                            </Message>

                            <div v-else class="flex flex-col gap-3">
                                <Link
                                    v-for="quest in props.quests"
                                    :key="quest.id"
                                    :href="route('quests.show', { quest: quest.id })"
                                    class="related-card"
                                >
                                    <div class="quest-icon">
                                        <i class="pi pi-book" />
                                    </div>
                                    <div class="min-w-0 grow">
                                        <div class="truncate font-semibold">#{{ quest.id }} - {{ quest.name }}</div>
                                        <div class="text-sm text-surface-500">
                                            {{ quest.is_daily ? 'Quest dzienny' : 'Quest standardowy' }}
                                        </div>
                                    </div>
                                    <Tag :value="quest.is_daily ? 'Daily' : 'Quest'" :severity="quest.is_daily ? 'warn' : 'info'" />
                                </Link>
                            </div>
                        </section>
                    </div>
                </TabPanel>

                <TabPanel value="history" class="dialog-scroll-panel">
                    <DialogActivityLogsTable :logs="props.logs" />
                </TabPanel>
            </TabPanels>
        </Tabs>
    </AppLayout>
</template>

<style scoped>
.dialog-tabs {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: calc(100vh - 1.5rem);
    min-height: 0;
}

.dialog-tabs :deep(.p-tabpanels) {
    display: flex;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    padding: 1rem 0 0;
    background: transparent;
}

.dialog-tabs :deep(.p-tabpanel) {
    width: 100%;
    min-height: 0;
}

.dialog-tree-panel {
    height: 100%;
    overflow: hidden;
}

.dialog-scroll-panel {
    height: 100%;
    overflow: auto;
}

.dialog-flow-shell,
.dialog-section {
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    background: var(--surface-card);
}

.dialog-flow-shell {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.dialog-flow-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--surface-border);
}

.dialog-flow {
    flex: 1;
    height: auto;
    min-height: 0;
}

.dialog-section {
    padding: 1rem;
}

:deep(.vue-flow__edge-path) {
    transition: stroke 0.3s, stroke-width 0.3s, stroke-opacity 0.3s;
}

:deep(.vue-flow__edge:hover .vue-flow__edge-path) {
    stroke-width: 3px !important;
    stroke-opacity: 0.95 !important;
}

:deep(.vue-flow__edge.selected .vue-flow__edge-path) {
    stroke-width: 3px !important;
    stroke-opacity: 0.95 !important;
}

:deep(.vue-flow__edge.animated .vue-flow__edge-path) {
    stroke-dasharray: 5, 5;
    animation: dashdraw 0.5s linear infinite;
}

@keyframes dashdraw {
    from {
        stroke-dashoffset: 10;
    }
}

.related-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem;
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    background: color-mix(in srgb, var(--surface-card) 92%, white 8%);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.related-card:hover {
    border-color: var(--primary-300);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
}

.quest-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ede9fe;
    color: #6d28d9;
    flex-shrink: 0;
}

.node-dnd-palette {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.5rem;
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    background: color-mix(in srgb, var(--surface-card) 92%, white 8%);
}

.node-dnd-title {
    font-size: 0.75rem;
    color: var(--text-color-secondary);
    font-weight: 600;
}

.node-dnd-item {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    border: 1px solid var(--surface-border);
    background: var(--surface-card);
    border-radius: 8px;
    padding: 0.35rem 0.55rem;
    font-size: 0.8rem;
    cursor: grab;
}

.node-dnd-item:active {
    cursor: grabbing;
}
</style>
