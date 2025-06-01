<script setup lang="ts">
import AppLayout from '../../layout/AppLayout.vue';

import { MiniMap } from '@vue-flow/minimap';
import {ConnectionMode, EdgeRemoveChange, NodeProps, BezierEdge, useVueFlow, VueFlow} from '@vue-flow/core';

import SpecialNode from '@/Pages/Dialog/SpecialNode.vue';
import { Controls } from '@vue-flow/controls';
import StartNode from '@/Pages/Dialog/StartNode.vue';
import ShopNode from '@/Pages/Dialog/ShopNode.vue';
import { DialogResource } from '@/Resources/Dialog.resource';
// import { DialogConnectionResource } from '@/Resources/DialogConnection.resource';
import { computed, ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import TeleporationNode from '@/Pages/Dialog/TeleporationNode.vue';
import {useToast} from "primevue";

const props = defineProps<{
    dialog: DialogResource,
    nodes: any[], //todo
    edges: any[],
}>();

const {
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
    removeEdges,
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
        case 'shop':
            return '#7a1a10';
        default:
            return '#888';
    }
}

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
                sourceNodeIsInput: change.item.sourceNode.type == 'start',
                sourceNodeId: change.item.sourceNode.id,
                sourceOptionId: change.item.sourceNode.type != 'start' ? change.item.sourceHandle.substring(7) : null,
                targetNodeId: change.item.targetNode.id
            }).then(({ data: { edge } }) => {
                console.log(' add edge from backend ---<', edge, props.dialog)
                let edgeTmp = change;
                edgeTmp.item.id = edge.id;
                applyEdgeChanges([edgeTmp]);

                // Update the option's edges list
                if (change.item.sourceNode.type !== 'start' && change.item.sourceHandle) {
                    const sourceNodeId = change.item.sourceNode.id;
                    const sourceOptionId = change.item.sourceHandle.substring(7);
                    const targetNodeId = change.item.targetNode.id;

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
    // Find the edge in the edges array
    const edge = props.edges.find(e => e.id === edgeChange.id);

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
    }
]);
</script>

<template>
    <AppLayout>
<!--        <pre v-text="startEdges" />-->
<!--        <pre v-text="edges" />-->
        <div class="">


            <VueFlow
                :nodes="startNodes"
                :edges="startEdges"
                :connection-mode="ConnectionMode.Strict"
                :max-zoom="1"
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
                <template #node-teleportation="teleportationNodeProps">
                    <!--suppress RequiredAttributes -->
                    <TeleporationNode v-bind="teleportationNodeProps" />
                </template>

                <template #edge-default="customEdgeProps">
                    <BezierEdge
                        :id="customEdgeProps.id"
                        :source-x="customEdgeProps.sourceX"
                        :source-y="customEdgeProps.sourceY"
                        :target-x="customEdgeProps.targetX"
                        :target-y="customEdgeProps.targetY"
                        :source-position="customEdgeProps.sourcePosition"
                        :target-position="customEdgeProps.targetPosition"
                        :data="customEdgeProps.data"
                        :marker-end="customEdgeProps.markerEnd"
                        :style="{ strokeWidth: '2px', stroke: '#6366f1' }"
                        :path-options="{ curvature: 0.5 }"
                    />
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
</style>
