<script setup lang="ts">
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import axios from "axios";
import {route} from "ziggy-js";
import {useVueFlow} from "@vue-flow/core";
import {useConfirm, useToast} from "primevue";

const {
    dialogNodeId,
    dialogId
} = defineProps<{
    dialogId: number
    dialogNodeId: string
}>()

const { updateNodeData, edges, removeEdges, removeNodes, connectionLookup, applyNodeChanges } = useVueFlow();

const confirm = useConfirm();
const toast = useToast();

const remove = (event) => {

    confirm.require({
        target: event.currentTarget,
        message: 'Usunąć tą kwestię dialogową?',
        icon: 'pi pi-info-circle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Delete',
            severity: 'danger'
        },
        accept: () => {

            axios.delete(route('dialogs.nodes.destroy', {
                dialog: dialogId,
                dialogNode: dialogNodeId,
            }))
                .then(() => {
                    applyNodeChanges([{
                        type: 'remove',
                        id: dialogNodeId,
                    }]);
                    toast.add({ severity: 'info', summary: 'Udało się', detail: 'Usunięto', life: 3000 });
                })
                .catch(({response}) => {
                    console.log('data', response.data)
                    toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
                })

        },
    });
}
</script>
<template>
    <Button severity="danger" size="small" class="align-self-end" @click="remove($event)">
        <FontAwesomeIcon icon="trash" />
    </Button>
</template>

<style scoped>

</style>
