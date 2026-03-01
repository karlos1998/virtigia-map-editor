<script setup lang="ts">
import AppLayout from '../../layout/AppLayout.vue';

import { MiniMap } from '@vue-flow/minimap';
import { ConnectionMode, EdgeRemoveChange, NodeProps, useVueFlow, VueFlow } from '@vue-flow/core';

import SpecialNode from '@/Pages/Dialog/SpecialNode.vue';
import { Controls } from '@vue-flow/controls';
import StartNode from '@/Pages/Dialog/StartNode.vue';
import ShopNode from '@/Pages/Dialog/ShopNode.vue';
import RandomizerNode from '@/Pages/Dialog/RandomizerNode.vue';
import DialogEdge from '@/Pages/Dialog/DialogEdge.vue';
import DialogActivityLogsTable from '@/Pages/Dialog/Partials/DialogActivityLogsTable.vue';
import { DialogResource } from '@/Resources/Dialog.resource';
import { NpcWithLocationsResource } from '@/Resources/Npc.resource';
import { SimpleQuestResource } from '@/Resources/Quest.resource';
import type { AdvanceTableResponse } from '@/karlos3098-LaravelPrimevueTable/Services/tableService';
// import { DialogConnectionResource } from '@/Resources/DialogConnection.resource';
import { computed, ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import TeleporationNode from '@/Pages/Dialog/TeleporationNode.vue';
import {useToast} from "primevue";
import EditDialogNameDialog from '@/Pages/Dialog/Modals/EditDialogNameDialog.vue';
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import { Link, useForm } from '@inertiajs/vue3';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps<{
    dialog: DialogResource,
    nodes: any[], //todo
    edges: any[],
    npcs: NpcWithLocationsResource[],
    quests: SimpleQuestResource[],
    logs: AdvanceTableResponse<any>,
}>();

const isEditDialogNameVisible = ref(false);
const confirm = useConfirm();

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
    onNodeDragStop,
    onEdgesChange,
    applyEdgeChanges,
    onNodesChange,
    applyNodeChanges,
    updateNodeData,
} = useVueFlow();

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
        default:
            return '#888';
    }
}

const isDirectOutputNode = (type?: string): boolean => {
    return type === 'start' || type === 'randomizer';
};

const addNode = (type?: string) => {
    axios.post(route('dialogs.nodes.store', { dialog: props.dialog.id }), {
        position: {
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
                targetNodeId: change.item.targetNode.id
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

const toast = useToast();
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

    if (edge && edge.source && edge.sourceHandle) {
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
    } else if (edge?.source) {
        const sourceNode = nodes.value.find((node) => node.id === edge.source);
        if (sourceNode?.data?.edges) {
            updateNodeData(edge.source, {
                edges: sourceNode.data.edges.filter((item) => item.edge_id != edgeChange.id)
            });
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
        label: 'Teleport',
        icon: 'pi pi-forward',
        command: () => {
            addNode('teleportation');
        }
    },
    {
        label: 'Losowanie',
        icon: 'pi pi-percentage',
        command: () => {
            addNode('randomizer');
        }
    }
]);
</script>

<template>
    <AppLayout>
        <ConfirmDialog group="dialog-show-modal" />

        <EditDialogNameDialog :dialog="props.dialog" v-model:visible="isEditDialogNameVisible" />

        <DetailsCardList title="Informacje o dialogu" class="mb-4">
            <DetailsCardListItem label="Nazwa">
                <template #value>
                    <div class="flex items-center justify-between">
                        <span>{{ props.dialog.name }}</span>
                        <div class="flex gap-2">
                            <Button @click="isEditDialogNameVisible = true" label="Edytuj nazwę" size="small" />
                            <Button @click="copyDialog" :loading="copyForm.processing" label="Kopiuj dialog" size="small" severity="secondary" />
                        </div>
                    </div>
                </template>
            </DetailsCardListItem>
        </DetailsCardList>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Powiązane NPC</h2>
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
                        <img :src="npc.src" :alt="npc.name" class="w-12 h-12 rounded-md object-cover bg-surface-100" />
                        <div class="min-w-0 grow">
                            <div class="font-semibold truncate">#{{ npc.id }} - {{ npc.name }}</div>
                            <div class="text-sm text-surface-500">Poziom {{ npc.lvl }}</div>
                            <div v-if="npc.locations?.length" class="text-sm text-surface-500 truncate">
                                [{{ npc.locations[0].map_id }}] {{ npc.locations[0].map_name }} ({{ npc.locations[0].x }},{{ npc.locations[0].y }})
                            </div>
                            <div v-else class="text-sm text-surface-400">
                                Brak lokalizacji
                            </div>
                        </div>
                        <Tag :value="npc.enabled ? 'Aktywny' : 'Wyłączony'" :severity="npc.enabled ? 'success' : 'danger'" />
                    </Link>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Powiązane Questy</h2>
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
                            <div class="font-semibold truncate">#{{ quest.id }} - {{ quest.name }}</div>
                            <div class="text-sm text-surface-500">
                                {{ quest.is_daily ? 'Quest dzienny' : 'Quest standardowy' }}
                            </div>
                        </div>
                        <Tag :value="quest.is_daily ? 'Daily' : 'Quest'" :severity="quest.is_daily ? 'warn' : 'info'" />
                    </Link>
                </div>
            </div>
        </div>

<!--        <pre v-text="startEdges" />-->
<!--        <pre v-text="edges" />-->
        <div class="">


            <VueFlow
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
                    type: 'bezier',
                    style: { strokeWidth: '2px', stroke: '#6366f1' },
                    animated: true
                }"
                :elevate-edges-on-select="true"
            >
                <!-- bind your custom node type to a component by using slots, slot names are always `node-<type>` -->
                <template #node-special="specialNodeProps">
                    <!--suppress RequiredAttributes -->
                    <SpecialNode v-bind="specialNodeProps" />
                </template>
                <template #node-start="startNodeProps">
                    <!--suppress RequiredAttributes -->
                    <StartNode v-bind="startNodeProps" />
                </template>
                <template #node-shop="shopNodeProps">
                    <!--suppress RequiredAttributes -->
                    <ShopNode v-bind="shopNodeProps" />
                </template>
                <template #node-randomizer="randomizerNodeProps">
                    <!--suppress RequiredAttributes -->
                    <RandomizerNode v-bind="randomizerNodeProps" />
                </template>
                <template #node-teleportation="teleportationNodeProps">
                    <!--suppress RequiredAttributes -->
                    <TeleporationNode v-bind="teleportationNodeProps" />
                </template>

                <template #edge-default="customEdgeProps">
                    <DialogEdge v-bind="customEdgeProps" />
                </template>

                <Controls position="top-left">
                    <!--                    <Button @click="addNode">-->
                    <!--                        <FontAwesomeIcon icon="plus" />-->
                    <!--                    </Button>-->

                    <SpeedDial :model="items" direction="up" />

                    <!--                    <Button @click="saveDialog">-->
                    <!--                        <FontAwesomeIcon icon="save" />-->
                    <!--                    </Button>-->
                </Controls>

                <MiniMap :node-stroke-color="nodeStroke" :node-color="nodeColor" />
            </VueFlow>
        </div>

        <DialogActivityLogsTable :logs="props.logs" />
    </AppLayout>
</template>

<style scoped>
/* Custom edge styling */
:deep(.vue-flow__edge) {
    transition: stroke 0.3s, stroke-width 0.3s;
}

:deep(.vue-flow__edge:hover) {
    stroke-width: 3px !important;
    stroke: #4f46e5 !important; /* Darker indigo on hover */
}

:deep(.vue-flow__edge.selected) {
    stroke-width: 3px !important;
    stroke: #4f46e5 !important; /* Darker indigo when selected */
}

:deep(.vue-flow__edge-path) {
    stroke-dasharray: none; /* Remove any dash pattern */
}

:deep(.vue-flow__edge.animated .vue-flow__edge-path) {
    stroke-dasharray: 5, 5; /* Add dash pattern for animated edges */
    animation: dashdraw 0.5s linear infinite;
}

@keyframes dashdraw {
    from {
        stroke-dashoffset: 10;
    }
}

.card {
    background-color: var(--surface-card);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.related-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem;
    border: 1px solid var(--surface-border);
    border-radius: 12px;
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
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #6d28d9;
    flex-shrink: 0;
}
</style>
