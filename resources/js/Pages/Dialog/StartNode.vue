<script setup lang="ts">
import { Handle, NodeProps, Position } from '@vue-flow/core';

const props = defineProps<NodeProps>();
</script>

<template>
    <div class="vue-flow__node-default">
        Start
        <Handle v-tooltip.top="'Drag to connect to dialog, right click to remove connections'"
                id="source-source-1"
                class="dialog-output" type="source" :position="Position.Right" />
    </div>
</template>

<style scoped>
.vue-flow__node-default {
    @apply bg-green-200;
}
</style>
