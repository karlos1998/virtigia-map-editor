<script setup lang="ts">
import {Handle, Position, useVueFlow} from '@vue-flow/core';
import { useDialog } from 'primevue/usedialog';
import { computed } from 'vue';
import {DialogNodeTeleportationDataResource} from "../../Resources/DialogNodeTeleportationData.resource";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import TeleportationSelectModal from "../../Components/TeleportationSelectModal.vue";
import {DynamicDialogCloseOptions} from "primevue/dynamicdialogoptions";
import {route} from "ziggy-js";
import axios from "axios";
import RemoveNodeButton from "./Componnts/RemoveNodeButton.vue";

const { updateNodeData } = useVueFlow();

const props = defineProps<{
    id: string
    data: {
        dialog_id: number
        action_data?: {
            teleportation?: DialogNodeTeleportationDataResource,
        }
    }
}>();

const primeDialog = useDialog();
const teleportation = computed(() => props.data.action_data?.teleportation ?? null);
const hasInstance = computed(() => Boolean(teleportation.value?.createInstance));
const includeNpcs = computed(() => Boolean(teleportation.value?.includeNpcs));
const scaleNpcs = computed(() => Boolean(teleportation.value?.scaleNpcsToPlayerLevel));
const mapTitle = computed(() => teleportation.value?.mapName || 'Nie wybrano mapy');
const mapIdLabel = computed(() => teleportation.value?.mapId ? `#${teleportation.value.mapId}` : 'Brak ID');
const coordinateLabel = computed(() => teleportation.value ? `${teleportation.value.x}, ${teleportation.value.y}` : '--, --');

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
            teleportation: teleportation.value
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
                        updateNodeData(dialogNode.id.toString(), dialogNode.data);
                    })
            }
        }
    });
}
</script>

<template>
    <div class="vue-flow__node-default teleport-node" :class="{ 'teleport-node--instance': hasInstance }">
        <Handle class="dialog-input" type="target" :position="Position.Left" />

        <div class="teleport-node__header">
            <div class="teleport-node__title">
                <span class="teleport-node__icon">
                    <i :class="hasInstance ? 'pi pi-clone' : 'pi pi-send'" />
                </span>
                <div>
                    <span>{{ hasInstance ? 'Teleport instancyjny' : 'Teleport' }}</span>
                    <small>{{ hasInstance ? 'Tworzy osobną mapę' : 'Akcja dialogowa' }}</small>
                </div>
            </div>

            <div class="teleport-node__actions">
                <Button severity="info" size="small" text rounded aria-label="Edytuj teleport" @click="editNode">
                    <FontAwesomeIcon icon="edit" />
                </Button>
                <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />
            </div>
        </div>

        <div class="teleport-node__body" :class="{ 'teleport-node__body--empty': !teleportation }">
            <template v-if="teleportation">
                <div v-if="hasInstance" class="teleport-node__instance-banner">
                    <i class="pi pi-sitemap" />
                    <div>
                        <strong>INSTANCJA MAPY</strong>
                        <span>osobna kopia lokacji</span>
                    </div>
                </div>

                <div class="teleport-node__map">
                    <span class="teleport-node__map-id">{{ mapIdLabel }}</span>
                    <strong>{{ mapTitle }}</strong>
                </div>

                <div class="teleport-node__coords">
                    <i class="pi pi-map-marker" />
                    <span>{{ coordinateLabel }}</span>
                </div>

                <div class="teleport-node__badges">
                    <span class="teleport-node__badge" :class="{ 'teleport-node__badge--active': hasInstance }">
                        {{ hasInstance ? 'Instancja' : 'Zwykły teleport' }}
                    </span>
                    <span v-if="hasInstance" class="teleport-node__badge" :class="{ 'teleport-node__badge--active': includeNpcs }">
                        {{ includeNpcs ? 'NPC z mapy' : 'Bez NPC' }}
                    </span>
                    <span v-if="hasInstance && includeNpcs" class="teleport-node__badge" :class="{ 'teleport-node__badge--active': scaleNpcs }">
                        {{ scaleNpcs ? 'Skalowanie lvl' : 'Stały lvl' }}
                    </span>
                </div>
            </template>

            <template v-else>
                <i class="pi pi-map" />
                <span>Nie ustawiono miejsca teleportacji</span>
            </template>
        </div>
    </div>
</template>

<style scoped lang="scss">
.teleport-node {
    @apply text-left text-white overflow-hidden;

    width: 318px;
    min-height: 184px;
    padding: 0;
    border: 1px solid rgba(151, 189, 255, 0.42);
    border-radius: 14px;
    background:
        linear-gradient(145deg, rgba(12, 24, 45, 0.94), rgba(22, 34, 63, 0.92)),
        url(@/assets/images/console-back.jpg);
    background-size: cover;
    box-shadow: 0 18px 38px rgba(4, 10, 22, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.teleport-node--instance {
    border-color: rgba(250, 204, 21, 0.7);
    background:
        linear-gradient(145deg, rgba(48, 30, 7, 0.96), rgba(10, 48, 38, 0.94)),
        url(@/assets/images/console-back.jpg);
    background-size: cover;
    box-shadow: 0 20px 44px rgba(120, 53, 15, 0.35), 0 0 0 1px rgba(34, 197, 94, 0.28), inset 0 1px 0 rgba(254, 240, 138, 0.16);
}

.teleport-node__header {
    @apply flex items-start justify-between gap-3;

    padding: 12px 12px 10px;
    border-bottom: 1px solid rgba(151, 189, 255, 0.18);
    background: rgba(5, 11, 24, 0.45);
}

.teleport-node--instance .teleport-node__header {
    border-bottom-color: rgba(250, 204, 21, 0.26);
    background: linear-gradient(90deg, rgba(113, 63, 18, 0.62), rgba(20, 83, 45, 0.42));
}

.teleport-node__title {
    @apply flex items-center gap-2 min-w-0;

    span {
        @apply block font-bold leading-tight;
        color: #f8fafc;
    }

    small {
        @apply block text-xs uppercase tracking-wide;
        color: rgba(191, 219, 254, 0.72);
    }
}

.teleport-node--instance .teleport-node__title {
    span {
        color: #fef3c7;
    }

    small {
        color: rgba(187, 247, 208, 0.78);
    }
}

.teleport-node__icon {
    @apply inline-grid place-items-center shrink-0;

    width: 34px;
    height: 34px;
    border-radius: 10px;
    color: #bfdbfe;
    background: rgba(59, 130, 246, 0.22);
    border: 1px solid rgba(147, 197, 253, 0.34);
}

.teleport-node--instance .teleport-node__icon {
    color: #fde68a;
    background: rgba(245, 158, 11, 0.24);
    border-color: rgba(253, 224, 71, 0.44);
    box-shadow: 0 0 22px rgba(250, 204, 21, 0.18);
}

.teleport-node__actions {
    @apply flex items-center gap-1;

    :deep(.p-button) {
        width: 2rem;
        height: 2rem;
        color: #dbeafe;
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.12);

        &:hover {
            background: rgba(96, 165, 250, 0.22);
        }
    }

    :deep(.p-button-danger) {
        color: #fecaca;

        &:hover {
            background: rgba(239, 68, 68, 0.24);
        }
    }
}

.teleport-node--instance .teleport-node__actions {
    :deep(.p-button) {
        color: #fef3c7;
        background: rgba(250, 204, 21, 0.12);
        border-color: rgba(253, 224, 71, 0.22);

        &:hover {
            background: rgba(34, 197, 94, 0.22);
        }
    }
}

.teleport-node__body {
    @apply flex flex-col gap-3;

    padding: 14px;
}

.teleport-node__instance-banner {
    @apply flex items-center gap-2;

    padding: 9px 10px;
    border-radius: 12px;
    color: #fef9c3;
    background: linear-gradient(90deg, rgba(234, 179, 8, 0.24), rgba(34, 197, 94, 0.17));
    border: 1px solid rgba(250, 204, 21, 0.38);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);

    i {
        @apply text-lg shrink-0;
        color: #fde047;
    }

    strong,
    span {
        @apply block leading-tight;
    }

    strong {
        @apply text-xs tracking-wide;
    }

    span {
        @apply text-xs;
        color: rgba(220, 252, 231, 0.76);
    }
}

.teleport-node__body--empty {
    @apply items-center justify-center text-center;

    min-height: 112px;
    color: rgba(226, 232, 240, 0.72);
    border: 1px dashed rgba(148, 163, 184, 0.42);
    margin: 12px;
    border-radius: 12px;
    padding: 18px;

    i {
        @apply text-2xl;
        color: rgba(147, 197, 253, 0.78);
    }
}

.teleport-node__map {
    @apply min-w-0;

    strong {
        @apply block text-base leading-tight truncate;
        color: #fff7d6;
    }
}

.teleport-node__map-id {
    @apply inline-flex items-center text-xs font-bold mb-1;

    padding: 2px 7px;
    border-radius: 999px;
    color: #c7d2fe;
    background: rgba(99, 102, 241, 0.2);
    border: 1px solid rgba(165, 180, 252, 0.24);
}

.teleport-node--instance .teleport-node__map-id {
    color: #fef3c7;
    background: rgba(234, 179, 8, 0.2);
    border-color: rgba(253, 224, 71, 0.32);
}

.teleport-node--instance .teleport-node__map strong {
    color: #ecfccb;
}

.teleport-node__coords {
    @apply inline-flex items-center gap-2 font-bold;

    width: fit-content;
    padding: 6px 9px;
    border-radius: 10px;
    color: #bae6fd;
    background: rgba(14, 165, 233, 0.12);
    border: 1px solid rgba(125, 211, 252, 0.2);
}

.teleport-node--instance .teleport-node__coords {
    color: #fef08a;
    background: rgba(202, 138, 4, 0.14);
    border-color: rgba(250, 204, 21, 0.28);
}

.teleport-node__badges {
    @apply flex flex-wrap gap-1.5;
}

.teleport-node__badge {
    @apply text-xs font-semibold;

    padding: 4px 7px;
    border-radius: 999px;
    color: rgba(226, 232, 240, 0.82);
    background: rgba(15, 23, 42, 0.58);
    border: 1px solid rgba(148, 163, 184, 0.22);
}

.teleport-node__badge--active {
    color: #dcfce7;
    background: rgba(34, 197, 94, 0.16);
    border-color: rgba(134, 239, 172, 0.34);
}

.teleport-node--instance .teleport-node__badge {
    color: rgba(254, 243, 199, 0.86);
    background: rgba(24, 24, 27, 0.5);
    border-color: rgba(250, 204, 21, 0.18);
}

.teleport-node--instance .teleport-node__badge--active {
    color: #14532d;
    background: #bbf7d0;
    border-color: rgba(187, 247, 208, 0.8);
}

.dialog-input {
    @apply bg-red-400;

    top: 26px;
}

.teleport-node--instance .dialog-input {
    @apply bg-amber-400;
}

.dialog-output {
    @apply bg-blue-400 right-0;
}

</style>
