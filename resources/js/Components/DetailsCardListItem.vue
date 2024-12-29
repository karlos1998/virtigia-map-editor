<script setup lang="ts">
import {computed, useSlots} from "vue";

const props = defineProps<{
    label?: string;
    value?: string | number | null;
}>();

const slots = useSlots();

const hasLabel = computed(() => !!props.label || !!slots.label);
if (!hasLabel.value) {
    console.warn('Label is required either as a prop or a slot.');
}
</script>

<template>
    <li class="flex items-center py-3 px-2">

        <div class="text-gray-500 flex-none w-1/2 md:w-1/3 font-medium">
            <slot name="label">
                {{ label }}
            </slot>
        </div>

        <div class="text-gray-900 flex-grow w-1/2 md:w-2/3">
            <slot name="value">
                {{ value ?? '-' }}
            </slot>
        </div>
    </li>
</template>
