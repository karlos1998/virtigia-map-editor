<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { ConnectionLookup, Handle, NodeProps, Position, useVueFlow } from '@vue-flow/core';
import RemoveNodeButton from './Componnts/RemoveNodeButton.vue';
import { DialogOptionResource } from '@/Resources/DialogOption.resource';

const props = defineProps<NodeProps<{
    dialog_id: number
    options: Array<DialogOptionResource>
}>>();

const options = ref(props.data.options ?? []);
const { edges, removeEdges, connectionLookup } = useVueFlow();
const handleHasConnections = reactive<Record<string, boolean>>({});

const removeSourceConnections = (option: { id: string | number }) => {
    const foundEdges = edges.value
        .filter((edge) => edge.source === props.id)
        .filter((edge) => edge.sourceHandle === `source-${option.id}`);
    removeEdges(foundEdges);
};

watch(connectionLookup, (value: ConnectionLookup) => {
    for (const option of options.value) {
        const optionConnections = value.get(`${props.id}-source-source-${option.id}`);
        handleHasConnections[`source-${option.id}`] = optionConnections?.size > 0;
    }
}, { deep: true, immediate: true, flush: 'post' });
</script>

<template>
    <div class="vue-flow__node-default">
        <Handle class="dialog-input" type="target" :position="Position.Left" />

        <div class="font-bold text-lg flex flex-row gap-1 mb-2">
            <span class="grow">Wybór profesji</span>
            <RemoveNodeButton :dialog-node-id="id" :dialog-id="data.dialog_id" />
        </div>

        <div class="text-sm text-surface-700 mb-2">
            Każda profesja ma jedno wyjście.
        </div>

        <div class="options">
            <div
                v-for="option in options"
                :key="option.id"
                class="option flex items-center gap-2"
                :class="{ exit: !handleHasConnections[`source-${option.id}`] }"
            >
                <span class="option-label grow">{{ option.label }}</span>
                <Handle
                    v-tooltip.top="'Drag to connect to dialog, right click to remove connections'"
                    :id="`source-${option.id}`"
                    class="dialog-output"
                    type="source"
                    :position="Position.Right"
                    @contextmenu.prevent="removeSourceConnections(option)"
                />
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.vue-flow__node-default {
    @apply min-w-96 min-h-56 text-left p-4;
    background: #e9f5ff;
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
        @apply relative p-2 mb-1 bg-white rounded border border-sky-200;
        color: #0f3250;

        &.exit {
            color: #9a4f00;
            border-color: #f59e0b;
        }
    }
}
</style>
