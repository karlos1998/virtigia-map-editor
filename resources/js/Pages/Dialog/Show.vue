<script setup lang="ts">
import AppLayout from '../../layout/AppLayout.vue';

import { MiniMap } from '@vue-flow/minimap';
import { ConnectionMode, NodeProps, useVueFlow, VueFlow } from '@vue-flow/core';

import SpecialNode from '@/Pages/Dialog/SpecialNode.vue';
import { Controls } from '@vue-flow/controls';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import StartNode from '@/Pages/Dialog/StartNode.vue';
import { DialogGroupResource } from '@/Resources/DialogGroup.resource';
import { DialogResource } from '@/Resources/Dialog.resource';
// import { DialogConnectionResource } from '@/Resources/DialogConnection.resource';
import { DialogOptionResource } from '@/Resources/DialogOption.resource';
import {computed} from "vue";
import axios from "axios";
import {route} from "ziggy-js";

const props = defineProps<{
    dialog: DialogResource,
    nodes: any[], //todo
    edges: any[],
}>();

const { nodes, onConnect, findNode, addEdges, addNodes, viewport, edges, onNodeDragStop, onEdgesChange, applyEdgeChanges, onNodesChange, applyNodeChanges } = useVueFlow();

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
        default:
            return '#eee';
    }
}

function nodeColor(n: NodeProps) {
    if (n.type === 'special') {
        return '#112e4e';
    }

    return '#fff';
}

const addNode = () => {
    axios.post(route('dialogs.nodes.store', {dialog: props.dialog.id}), {
        position: {
            x: -viewport.value.x,
            y: -viewport.value.y
        }
    }).then(({data: {node}}) => {
        console.log('add node ->', node)
        addNodes([node]);
    });
};

onNodeDragStop(({node}) => {
    axios.post(route('dialogs.nodes.move', {
        dialog: props.dialog.id,
        dialogNode: node.id,
    }), {
        position: node.position,
    }).catch((error) => {
        //todo - toast ze nie udalo sie przeniesc
    });
})

const startNodes = computed(() => props.nodes);
const startEdges = computed(() => props.edges);



onNodesChange(async (changes) => {
    const nextChanges = []

    console.log('onNodesChange', changes);

    for (const change of changes) {
        if (change.type === 'remove') {
            if(nodes.value.find((node) => node.id.toString() === change.id && node.type === 'input')) {
                //...
            } else {
                // confirmDeleteNode(change);
            }
        } else if (change.type === 'select' && change.selected) {
            // console.log('selected node: ' + change.id)
        } else {
            nextChanges.push(change)
        }
    }

    applyNodeChanges(nextChanges)
})

onEdgesChange(async (changes) => {
    const nextChanges = []

    console.log('onEdgesChange', changes);

    for (const change of changes) {
        if (change.type === 'add') {
            axios.post(route('dialogs.edges.store', {
                dialog: props.dialog.id,
            }), {
                sourceNodeIsInput: change.item.sourceNode.type == 'start',
                sourceNodeId: change.item.sourceNode.id,
                sourceOptionId: change.item.sourceNode.type != 'start' ?  change.item.sourceHandle.substring(7) : null,
                targetNodeId: change.item.targetNode.id,
            }).then(({data: {edge}}) => {

            }).catch((error) => {
                applyEdgeChanges([{
                    type: 'remove',
                    id: change.item.id,
                    source: change.item.source,
                    sourceHandle: change.item.sourceHandle,
                    target: change.item.target,
                    targetHandle: change.item.targetHandle,
                }])
            })
            nextChanges.push(change)
        } else if (change.type === 'remove') {
            //todo ....
        } else {
            nextChanges.push(change)
        }
    }

    applyEdgeChanges(nextChanges)
})

// import { DialogRuleResource } from '@/Resources/DialogRule.resource';

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

                <Controls>
                    <Button @click="addNode">
                        <FontAwesomeIcon icon="plus" />
                    </Button>
<!--                    <Button @click="saveDialog">-->
<!--                        <FontAwesomeIcon icon="save" />-->
<!--                    </Button>-->
                </Controls>

                <MiniMap :node-stroke-color="nodeStroke" :node-color="nodeColor" />
            </VueFlow>
        </div>
    </AppLayout>
</template>
