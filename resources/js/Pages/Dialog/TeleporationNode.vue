<script setup lang="ts">
import { Handle, NodeProps, Position } from '@vue-flow/core';
import { useDialog } from 'primevue/usedialog';
import axios from 'axios';
import { route } from 'ziggy-js';
import { debounce } from 'chart.js/helpers';
import { ref } from 'vue';

const primeDialog = useDialog();

interface Option {
    id: string,
    label: string,
}

const props = defineProps<NodeProps<{
    label: string,
    content: string,
    options: Array<Option>,
    action_data: any
}>>();

const selectedOption = ref<Option | null>(null);
const dropdownOptions = ref<Option[]>([]);

const searchOptions = debounce((event) => {
    axios.get(route('maps.search', { search: event.query }))
        .then(response => {
            dropdownOptions.value = response.data;
        });
}, 100);

console.log('props', props);
</script>

<script lang="ts">
export default {
    inheritAttrs: true
};
</script>

<template>
    <div class="vue-flow__node-default">
        <Handle class="dialog-input" type="target" :position="Position.Left" />
        <div class="font-bold text-lg flex flex-row gap-1 w-full">
            <Dropdown v-model="selectedOption" :options="dropdownOptions" filter optionLabel="name"
                      placeholder="Szukaj lokacji" class="w-full" @filter="searchOptions">
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex align-items-center">
                        <div>{{ slotProps.value.name }}</div>
                    </div>
                    <span v-else>
                        {{ slotProps.placeholder }}
                    </span>
                </template>
                <template #option="slotProps">
                    <div class="flex align-items-center">
                        <div>{{ slotProps.option.name }}</div>
                    </div>
                </template>
            </Dropdown>
        </div>
    </div>
</template>

<style scoped lang="scss">
.vue-flow__node-default {
    @apply text-white text-left flex flex-row gap-1 p-2;
}

.dialog-input {
    @apply top-6 bg-red-400;
}

.dialog-output {
    @apply bg-blue-400 right-0;
}

</style>
