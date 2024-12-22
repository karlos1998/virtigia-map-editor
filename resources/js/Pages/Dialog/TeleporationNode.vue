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
            test
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

</style>
