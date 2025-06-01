<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { ConnectionLookup, Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useDialog } from 'primevue/usedialog';
import EditDialog from '@/Pages/Dialog/Modals/EditDialog.vue';
import RemoveNodeButton from "./Componnts/RemoveNodeButton.vue";
import EditShopNodeDialog from "./Modals/EditShopNodeDialog.vue";
import axios from "axios";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import { Link } from '@inertiajs/vue3';
const primeDialog = useDialog();

const props = defineProps<NodeProps<{
    dialog_id: number
    shop?: {
        id: number
        name: string
        items_count: number
    }
}>>();

const { updateNodeData, edges, removeEdges, removeNodes, connectionLookup, } = useVueFlow();

const toast = useToast();

const editNode = () => {
    primeDialog.open(EditShopNodeDialog, {
        props: {
            header: 'Wybierz sklep z listy'
        },
        data: {
            // content: state.value.content
        },
        onClose(options) {

            if (options.data?.shop) {
                axios.put(route('dialogs.nodes.shop.assign', {
                    dialog: props.data.dialog_id,
                    dialogNode: props.id,
                }), {
                    shop_id: options.data.shop.id,
                })
                    .then(({data}) => {
                        updateNodeData(props.id, data.dialogNode.data);
                        toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie przypisano sklep', life: 6000 });
                    })
                    .catch(({response}) => {
                        toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
                    })
            }
        }
    });
};

</script>

<script lang="ts">
export default {
    inheritAttrs: true
};
</script>

<template>
    <div class="vue-flow__node-default flex flex-col h-full">
        <Handle class="dialog-input" type="target" :position="Position.Left" />

        <div class="font-bold text-lg flex flex-row gap-1">
            <Button severity="info" size="small" class="align-self-end" @click="editNode()">
                <FontAwesomeIcon icon="edit" />
            </Button>
            <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />
        </div>

        <div class="mt-auto mb-6" v-if="data.shop">
            <div>Nazwa sklepu: {{ data.shop.name }}</div>
            <div>Ilość przedmiotów: {{ data.shop.items_count }}</div>
            <Link :href="route('shops.show', data.shop.id)">
                <Button size="small">
                    Podgląd sklepu
                </Button>
            </Link>
        </div>
        <div v-else class="mt-auto mb-16 text-red-500">
            Nie wybrano sklepu
        </div>
    </div>
</template>


<style scoped lang="scss">
.vue-flow__node-default {
    @apply text-white text-left;

    width:269px;
    height:435px;

    background-image: url(@/assets/images/shop-main.png);
}

.dialog-input {
    @apply top-6 bg-red-400;
}

.dialog-output {
    @apply bg-blue-400 right-0;
}

.options {
    @apply flex flex-col mt-2;

    .option {
        @apply relative p-1 mb-1 bg-black/30;
        color: rgb(231, 215, 152);

        &:hover {
            @apply bg-white/30;
        }

        &:not(.add-option):before {
            content: '';

            @apply float-left mr-2 mt-1 bg-no-repeat;
            background: url(@/assets/images/dialog_icons.png);
            width: 12px;
            height: 12px;
        }

        &.exit {
            color: #fb4;

            &:before {
                background-position: -12px 0;
            }
        }
    }
}
</style>
