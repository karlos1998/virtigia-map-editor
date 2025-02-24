<script setup lang="ts">
import {Handle, NodeProps, Position, useVueFlow} from '@vue-flow/core';
import { useDialog } from 'primevue/usedialog';
import { ref } from 'vue';
import {DialogNodeTeleportationDataResource} from "../../Resources/DialogNodeTeleportationData.resource";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import TeleportationSelectModal from "../../Components/TeleportationSelectModal.vue";
import {DynamicDialogCloseOptions} from "primevue/dynamicdialogoptions";
import {router} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import axios from "axios";
import RemoveNodeButton from "./Componnts/RemoveNodeButton.vue";

const { updateNodeData, edges, removeEdges, removeNodes, connectionLookup } = useVueFlow();

const props = defineProps<{
    id: string
    data: {
        dialog_id: number
        action_data?: {
            teleportation: DialogNodeTeleportationDataResource,
        }
    }
}>();

console.log('props', props);


const primeDialog = useDialog();
const editNode = () => {

    primeDialog.open(TeleportationSelectModal, {
        props: {
            header: 'Edycja miejsca teleportacji',
            modal: true,
            breakpoints:{
                '960px': '75vw',
                '640px': '90vw'
            },
            style: 'max-width:90%',
        },
        data: {
            teleportation: props.data.action_data.teleportation
        },
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { teleportation: DialogNodeTeleportationDataResource } }) {
            if(closeOptions.data?.teleportation) {
                axios.patch(route('dialogs.nodes.action.update', {
                    dialog: props.data.dialog_id,
                    dialogNode: props.id
                }), {
                    teleportation: closeOptions.data.teleportation,
                })
                    .then(({data}) => {
                        const dialogNode = data.dialogNode;
                        console.log('new dialog node', dialogNode);
                        updateNodeData(dialogNode.id.toString(), dialogNode.data);
                    })
            }
        }
    });
}
</script>

<template>


<!--    <TeleportationSelectModal v-model:visible="isTeleportationSelectModalVisible" v-bind="data.action_data" />-->

    <div class="vue-flow__node-default">
        <Handle class="dialog-input" type="target" :position="Position.Left" />

        <Button severity="info" size="small" class="align-self-end" @click="editNode">
            <FontAwesomeIcon icon="edit" />
        </Button>

        <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />

        <div v-if="data.action_data.teleportation">
            [{{data.action_data.teleportation.mapId}}] {{data.action_data.teleportation.mapName}} ({{data.action_data.teleportation.x}}, {{data.action_data.teleportation.y}})
        </div>

    </div>
</template>

<style scoped lang="scss">
//.vue-flow__node-default {
//    @apply text-white text-left flex flex-row gap-1 p-2;
//}

.dialog-input {
    @apply top-6 bg-red-400;
}

.dialog-output {
    @apply bg-blue-400 right-0;
}

</style>
