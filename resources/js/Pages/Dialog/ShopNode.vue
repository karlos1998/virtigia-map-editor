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


const removeSourceConnections = (option: { label: string, id: string }) => {
    const foundEdges = edges.value.filter((edge) => edge.source === props.id)
        .filter((edge) => edge.sourceHandle === `source-${option.id}`);
    console.log('removeSourceConnections', foundEdges);
    removeEdges(foundEdges);
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
