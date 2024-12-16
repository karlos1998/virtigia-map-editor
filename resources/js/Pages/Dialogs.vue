<script setup lang="ts">
import AppLayout from '../layout/AppLayout.vue';

import { MiniMap } from '@vue-flow/minimap';
import { ConnectionMode, NodeProps, useVueFlow, VueFlow } from '@vue-flow/core';

import SpecialNode from '@/Pages/Dialogs/SpecialNode.vue';
import { Controls } from '@vue-flow/controls';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import StartNode from '@/Pages/Dialogs/StartNode.vue';
import { DialogGroupResource } from '../Resources/DialogGroup.resource';
import { DialogResource } from '@/Resources/Dialog.resource';
// import { DialogConnectionResource } from '@/Resources/DialogConnection.resource';
import { DialogOptionResource } from '@/Resources/DialogOption.resource';

const { nodes, onConnect, findNode, addEdges, addNodes, viewport } = useVueFlow();

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
            content: 'Dialog',
            options: []
        }
    });
};

const startNodes = [
    {
        id: '1',
        type: 'start',
        position: { x: 0, y: 0 },
        data: {
            label: 'NPC',
            content: 'Dialog',
            options: []
        }
    }
];


// import { DialogRuleResource } from '@/Resources/DialogRule.resource';

const saveDialog = () => {
    // TODO: export nodes to API

    const group: DialogGroupResource = {
        id: null // set id if group exists
    };

    const dialogs: DialogResource[] = [];
    for (const node of nodes.value) {
        if (node.type !== 'special') {
            continue;
        }

        const dialog: DialogResource = {
            id: null, // set id if dialog exists
            groupId: group.id,
            content: node.data.content,
            title: node.data.label,
            options: []
        };

        for (const option of node.data.options) {
            const dialogOption: DialogOptionResource = {
                id: null, // set id if option exists
                content: option.label,
                dialogId: dialog.id, // not really needed because we can get dialogId from dialog
                targetDialogs: []
            };
            // loop over edges, find target dialogs and add connections to dialogOption.targetDialogs
            // we need to defer this because we need dialogs ids, which are not set before saving dialogs (unless they already exist)

            dialog.options.push(dialogOption);
        }

        dialogs.push(dialog);
    }

    console.log('saveDialog', group, dialogs);
};
</script>

<template>
    <AppLayout>
        <div class="w-full h-full max-h-[85vh]">
            <VueFlow :nodes="startNodes" :connection-mode="ConnectionMode.Strict" :max-zoom="1" fit-view-on-init>
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
                    <Button @click="saveDialog">
                        <FontAwesomeIcon icon="save" />
                    </Button>
                </Controls>

                <MiniMap :node-stroke-color="nodeStroke" :node-color="nodeColor" />
            </VueFlow>
        </div>
    </AppLayout>
</template>
