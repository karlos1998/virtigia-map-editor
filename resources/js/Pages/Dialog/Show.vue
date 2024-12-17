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

const props = defineProps<{
    nodes: any[], //todo
    edges: any[],
}>();

const { nodes, onConnect, findNode, addEdges, addNodes, viewport, edges } = useVueFlow();

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
    addNodes({
        id: Math.random().toString(36).substring(3),
        type: 'special',
        position: {
            x: -viewport.value.x,
            y: -viewport.value.y
        },
        data: {
            label: 'NPC',
            content: 'DialogNode',
            options: []
        }
    });
};

const startNodes = computed(() => props.nodes);
const startEdges = computed(() => props.edges);

// import { DialogRuleResource } from '@/Resources/DialogRule.resource';

</script>

<template>
    <AppLayout>
<!--        <pre v-text="startEdges" />-->
<!--        <pre v-text="edges" />-->
        <div class="w-full h-full max-h-[85vh]">
            <VueFlow :nodes="startNodes" :edges="startEdges" :connection-mode="ConnectionMode.Strict" :max-zoom="1" fit-view-on-init>
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
