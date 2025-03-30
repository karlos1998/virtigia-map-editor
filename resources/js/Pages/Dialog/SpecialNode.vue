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
    // noinspection JSUnusedGlobalSymbols
    primeDialog.open(EditOption, {
        props: {
          header: 'Edycja opcji',
            modal: true,
        },
        data: {
            parent: props.id,
            option,
            dialog_id: props.data.dialog_id,
            additional_action: props.data.additional_actions,
        },
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { remove?: boolean, dialogOption?:DialogOptionResource } }) {
            if (closeOptions.data?.remove) {
                removeSourceConnections(option);
                options.value = options.value.filter((o) => o.id !== option.id);
            }

            if (closeOptions.data?.dialogOption) {
                option.label = closeOptions.data.dialogOption.label;
                option.additional_action = closeOptions.data.dialogOption.additional_action;
                console.log('new option data ', option)
            }

              updateNodeData(props.id, {
                options: [...options.value]
              });

              console.log(options.value);
        },
    });
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
            <div class="option" :class="{
                exit: !handleHasConnections[`source-${option.id}`]
            }" v-for="option in options" :key="option.id" @click="editOption(option)">
                {{ option.label }}
                <Handle v-tooltip.top="'Drag to connect to dialog, right click to remove connections'"
                        :id="`source-${option.id}`"
                        class="dialog-output" type="source" :position="Position.Right"
                        @contextmenu.prevent="removeSourceConnections(option)" />
            </div>
            <div class="option add-option" @click="addOption">
              <FontAwesomeIcon icon="plus" class="text-green-500 mr-2" />
                Dodaj opcję
            </div>
        </div>
    </div>
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
    }
}
</style>
