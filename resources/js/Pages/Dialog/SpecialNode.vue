<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { ConnectionLookup, Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useDialog } from 'primevue/usedialog';
import EditOption from '@/Pages/Dialog/Modals/EditOption.vue';
import { DynamicDialogCloseOptions } from 'primevue/dynamicdialogoptions';
import EditDialog from '@/Pages/Dialog/Modals/EditDialog.vue';

const primeDialog = useDialog();

interface Option {
    id: string,
    label: string,
}

const props = defineProps<NodeProps<{
    label: string,
    content: string,
    options: Array<Option>
}>>();

const { updateNodeData, edges, removeEdges, removeNodes, connectionLookup } = useVueFlow();

const state = ref({
    label: props.data.label ?? '',
  content: props.data.content ?? ''
});
const options = ref(props.data.options);

const editOption = (option: Option) => {
    // noinspection JSUnusedGlobalSymbols
    primeDialog.open(EditOption, {
        props: {
          header: 'Edit option'
        },
        data: {
            parent: props.id,
          option
        },
        onClose(closeOptions: DynamicDialogCloseOptions & { data: { remove?: boolean, label?: string } }) {
            if (closeOptions.data.remove) {
                removeSourceConnections(option);
                options.value = options.value.filter((o) => o.id !== option.id);
            }

            if (closeOptions.data.label) {
                option.label = closeOptions.data.label;
            }

          updateNodeData(props.id, {
            options: [...options.value]
          });
          console.log(options.value);
        }
    });
};

const removeSourceConnections = (option: { label: string, id: string }) => {
    const foundEdges = edges.value.filter((edge) => edge.source === props.id)
        .filter((edge) => edge.sourceHandle === `source-${option.id}`);
    console.log('removeSourceConnections', foundEdges);
    removeEdges(foundEdges);
};

const addOption = () => {
    const id = Math.random().toString(36).substring(3);
  options.value.push({ id, label: '' });
    editOption(options.value[options.value.length - 1]);
};

const editNode = () => {
    primeDialog.open(EditDialog, {
        props: {
          header: 'Edit dialog'
        },
        data: {
          content: state.value.content
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
    <div class="vue-flow__node-default">
      <Handle class="dialog-input" type="target" :position="Position.Left" />
        <div class="font-bold text-lg flex flex-row gap-1">
            <span class="grow">{{ state.label }}</span>
            <Button severity="info" size="small" class="align-self-end" @click="editNode()">
              <FontAwesomeIcon icon="edit" />
            </Button>
            <Button severity="danger" size="small" class="align-self-end" @click="removeNodes(props.id)">
              <FontAwesomeIcon icon="trash" />
            </Button>
        </div>
        <div>
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
