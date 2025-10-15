<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { ConnectionLookup, Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useDialog } from 'primevue/usedialog';
import EditOption from '@/Pages/Dialog/Modals/EditOption.vue';
import { DynamicDialogCloseOptions } from 'primevue/dynamicdialogoptions';
import EditDialog from '@/Pages/Dialog/Modals/EditDialog.vue';
import axios from "axios";
import {route} from "ziggy-js";
import {DialogOptionResource} from "@/Resources/DialogOption.resource";
import {useConfirm, useToast} from "primevue";
import RemoveNodeButton from "./Componnts/RemoveNodeButton.vue";
import {DialogNodeAdditionalActionsResource} from "../../Resources/DialogNodeAdditionalActions.resource";

const primeDialog = useDialog();
const showEditOption = ref(false);
const currentOption = ref<DialogOptionResource | null>(null);
import draggable from 'vuedraggable'

const props = defineProps<NodeProps<{
    dialog_id: number
    label: string,
    content: string,
    options: Array<DialogOptionResource>
    additional_actions: DialogNodeAdditionalActionsResource
}>>();

const { updateNodeData, edges, removeEdges, removeNodes, connectionLookup } = useVueFlow();

const state = ref({
    label: props.data.label ?? '',
  content: props.data.content ?? ''
});
const options = ref(props.data.options);

const editOption = (option: DialogOptionResource) => {
    currentOption.value = option;
    showEditOption.value = true;
};

const handleEditOptionClose = (closeData: { remove?: boolean, dialogOption?: DialogOptionResource }) => {

    console.log('handleEditOptionClose', closeData);

    if (!currentOption.value) return;

    if (closeData?.remove) {
        removeSourceConnections(currentOption.value);
        options.value = options.value.filter((o) => o.id !== currentOption.value!.id);
    }

    if (closeData?.dialogOption) {
        currentOption.value.label = closeData.dialogOption.label;
        currentOption.value.additional_action = closeData.dialogOption.additional_action;
        currentOption.value.rules = closeData.dialogOption.rules;
        currentOption.value.edges = closeData.dialogOption.edges;
        console.log('new option data ', currentOption.value);
    }

    updateNodeData(props.id, {
        options: [...options.value]
    });

    console.log(options.value);

    // Reset state
    showEditOption.value = false;
    currentOption.value = null;
};

const removeSourceConnections = (option: { label: string, id: string }) => {
    const foundEdges = edges.value.filter((edge) => edge.source === props.id)
        .filter((edge) => edge.sourceHandle === `source-${option.id}`);
    console.log('removeSourceConnections', foundEdges);
    removeEdges(foundEdges);
};

const addOption = async () => {
    const {data} = await axios.post<{option: DialogOptionResource}>(route('dialogs.nodes.options.store', {
        dialog: props.data.dialog_id,
        dialogNode: props.id,
    }))
    if(data && data?.option) {
        options.value.push(data.option);
    }
  //   const id = Math.random().toString(36).substring(3);
  // options.value.push({ id, label: '' });
  //   editOption(options.value[options.value.length - 1]);
};

const editNode = () => {
    primeDialog.open(EditDialog, {
        props: {
            header: 'Edytuj dialog',
            modal: true,
        },
        data: {
            content: state.value.content,
            dialog_id: props.data.dialog_id,
            node_id: props.id,
            additional_actions: props.data.additional_actions,
        },
        onClose(options) {
            if (options.data.content) {
                state.value.content = options.data.content;
            }

            // If the dialog was copied, refresh the page to show the new node
            if (options.data.copied) {
                window.location.reload();
            }
        }
    });
};

const handleHasConnections = reactive<Record<string, boolean>>({});

watch(connectionLookup, (value: ConnectionLookup) => {
    for (const i in options.value) {
        // find `${props.id}-source-source-${i}` in value.entries()
        const optionConnections = value.get(`${props.id}-source-source-${options.value[i].id}`);
        handleHasConnections[`source-${options.value[i].id}`] = optionConnections?.size > 0;
    }
    console.log('handleHasConnections', value, handleHasConnections);
}, { deep: true, immediate: true, flush: 'post' });

const saveOptionsOrder = async () => {
    const ids = options.value.map(o => o.id);
    await axios.post(route('dialogs.nodes.options.order', {
        dialog: props.data.dialog_id,
        dialogNode: props.id,
    }), {ids});
};
</script>

<script lang="ts">
export default {
    inheritAttrs: true
};
</script>

<template>

    <ConfirmPopup></ConfirmPopup>

    <div class="vue-flow__node-default">
      <Handle class="dialog-input" type="target" :position="Position.Left" />
        <div class="font-bold text-lg flex flex-row gap-1">
            <span class="grow">{{ state.label }}</span>
            <Button severity="info" size="small" class="align-self-end" @click="editNode()">
              <FontAwesomeIcon icon="edit" />
            </Button>

            <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />

        </div>
        <div class="whitespace-pre-wrap">
            {{ state.content }}
        </div>

        <div class="options">
            <draggable v-model="options" @end="saveOptionsOrder" @change="saveOptionsOrder" item-key="id"
                       class="flex flex-col"
                       handle=".drag-handle">
                <template #item="{element: option}">
                    <div class="option flex items-center gap-2"
                         :class="{
                         exit: !handleHasConnections[`source-${option.id}`] && !option.additional_action
                     }" @click="editOption(option)">
                        <span class="drag-handle cursor-grab select-none text-xl mr-2"
                              @mousedown.stop
                              @touchstart.stop
                              @click.stop
                              role="button"
                              aria-hidden="true">⠿</span>
                        <span class="option-label grow" @mousedown.stop>{{ option.label }}</span>
                        <Handle v-tooltip.top="'Drag to connect to dialog, right click to remove connections'"
                                :id="`source-${option.id}`"
                                class="dialog-output" type="source" :position="Position.Right"
                                @contextmenu.prevent="removeSourceConnections(option)"/>
                    </div>
                </template>
            </draggable>
            <div class="option add-option" @click="addOption">
              <FontAwesomeIcon icon="plus" class="text-green-500 mr-2" />
                Dodaj opcję
            </div>
        </div>
    </div>

    <!-- EditOption component -->
    <EditOption
        v-model:visible="showEditOption"
        @close="handleEditOptionClose"
        :option="currentOption"
        :parent="props.id"
        :dialog_id="props.data.dialog_id"
        :additional_action="props.data.additional_actions"
    />
</template>

<style scoped lang="scss">
.vue-flow__node-default {
    @apply min-w-96 min-h-56 text-white text-left;

    background-image: url(@/assets/images/console-back.jpg);
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

        .drag-handle {
            @apply inline-flex items-center justify-center w-8 h-8 mr-2 rounded select-none;
            cursor: grab;
        }

        .drag-handle:active {
            cursor: grabbing;
        }
    }
}
</style>
