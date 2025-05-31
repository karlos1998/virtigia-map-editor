<template>
    <TreeSelect
        :modelValue="modelValueWrapper"
        @update:modelValue="handleUpdate"
        :loading="loading"
        :options="options"
        @node-expand="onNodeExpand"
        placeholder="Wybierz krok questa"
        class="w-full md:w-80"
        selectionMode="single"
    />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: String,
    loading: Boolean,
    options: Array,
    onNodeExpand: Function
})

const emit = defineEmits(['update:modelValue'])

const modelValueWrapper = computed(() =>
    props.modelValue ? { [props.modelValue]: true } : null
)

function handleUpdate(val) {
    const firstKey = val ? Object.keys(val)[0] : null
    emit('update:modelValue', firstKey)
}
</script>
