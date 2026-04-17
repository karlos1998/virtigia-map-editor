<script setup lang="ts">
import { Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useDialog } from 'primevue/usedialog';
import RemoveNodeButton from './Componnts/RemoveNodeButton.vue';
import EditHotelNodeDialog from './Modals/EditHotelNodeDialog.vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import { Link } from '@inertiajs/vue3';

const primeDialog = useDialog();

const props = defineProps<NodeProps<{
    dialog_id: number
    hotel?: {
        id: number
        name: string
        rooms_count: number
    }
}>>();

const { updateNodeData } = useVueFlow();
const toast = useToast();

const editNode = (): void => {
    primeDialog.open(EditHotelNodeDialog, {
        props: {
            header: 'Wybierz hotel z listy',
        },
        onClose(options) {
            if (options.data?.hotel) {
                axios.put(route('dialogs.nodes.hotel.assign', {
                    dialog: props.data.dialog_id,
                    dialogNode: props.id,
                }), {
                    hotel_id: options.data.hotel.id,
                })
                    .then(({ data }) => {
                        updateNodeData(props.id, data.dialogNode.data);
                        toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie przypisano hotel', life: 6000 });
                    })
                    .catch(({ response }) => {
                        toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
                    });
            }
        },
    });
};
</script>

<script lang="ts">
export default {
    inheritAttrs: true,
};
</script>

<template>
    <div class="hotel-node flex flex-col h-full">
        <Handle class="dialog-input" type="target" :position="Position.Left" />

        <div class="font-bold text-lg flex flex-row gap-1 ml-2 mt-2">
            <Button severity="info" size="small" class="align-self-end" @click="editNode">
                <FontAwesomeIcon icon="edit" />
            </Button>
            <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />
        </div>

        <div class="px-2 pb-2 mt-auto">
            <div v-if="data.hotel" class="hotel-content">
                <div class="hotel-label">HOTEL</div>
                <div class="hotel-name" :title="data.hotel.name">{{ data.hotel.name }}</div>
                <div class="hotel-rooms">Pokoje: {{ data.hotel.rooms_count }}</div>
            </div>
            <div v-else class="hotel-empty">
                Nie wybrano hotelu
            </div>
            <Link v-if="data.hotel" :href="route('hotels.show', data.hotel.id)">
                <Button size="small" class="w-full mt-2">
                    Podgląd hotelu
                </Button>
            </Link>
        </div>
    </div>
</template>

<style scoped lang="scss">
.hotel-node {
    @apply text-white text-left;

    width: 170px;
    height: 180px;
    background: linear-gradient(165deg, #1e3a4a 0%, #214d63 60%, #1b6073 100%);
    border: 1px solid #74b8d4;
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
}

.dialog-input {
    @apply top-6 bg-sky-400;
}

.hotel-content {
    @apply p-2 rounded-md;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.24);
}

.hotel-label {
    font-size: 10px;
    letter-spacing: 0.08em;
    color: #c8edff;
}

.hotel-name {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hotel-rooms {
    font-size: 11px;
    color: #d6f2ff;
    margin-top: 4px;
}

.hotel-empty {
    @apply p-2 rounded-md text-center;
    font-size: 11px;
    color: #ffd5d5;
    background: rgba(120, 20, 20, 0.35);
    border: 1px solid rgba(255, 170, 170, 0.45);
}
</style>
