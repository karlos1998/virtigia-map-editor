<script setup lang="ts">
import AppLayout from '../../layout/AppLayout.vue';

import { MiniMap } from '@vue-flow/minimap';
import { ConnectionMode, NodeProps, SmoothStepEdge, useVueFlow, VueFlow } from '@vue-flow/core';

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
    applyNodeChanges
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
        console.log('add node ->', node);
        addNodes([node]);
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
            if (nodes.value.find((node) => node.id.toString() === change.id && node.type === 'input')) {
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

            }).catch((error) => {
                applyEdgeChanges([{
                    type: 'remove',
                    id: change.item.id,
                    source: change.item.source,
                    sourceHandle: change.item.sourceHandle,
                    target: change.item.target,
                    targetHandle: change.item.targetHandle
                }]);
            });
            nextChanges.push(change);
        } else if (change.type === 'remove') {
            //todo ....
        } else {
            nextChanges.push(change);
        }
    }

    applyEdgeChanges(nextChanges);
});

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
        <div class="w-full h-full max-h-[85vh]">
            <VueFlow
                :nodes="startNodes"
                :edges="startEdges"
                :connection-mode="ConnectionMode.Strict"
                :max-zoom="1"
                fit-view-on-init
                :apply-default="false"
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
                    <SmoothStepEdge
                        :id="customEdgeProps.id"
                        :source-x="customEdgeProps.sourceX"
                        :source-y="customEdgeProps.sourceY"
                        :target-x="customEdgeProps.targetX"
                        :target-y="customEdgeProps.targetY"
                        :source-position="customEdgeProps.sourcePosition"
                        :target-position="customEdgeProps.targetPosition"
                        :data="customEdgeProps.data"
                        :marker-end="customEdgeProps.markerEnd"
                    />
                </template>

                <Controls>
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
