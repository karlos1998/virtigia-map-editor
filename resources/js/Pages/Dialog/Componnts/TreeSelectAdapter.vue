<template>
    <TreeSelect
        :modelValue="modelValueWrapper"
        @update:modelValue="handleUpdate"
        :loading="loading"
        :options="options"
        @node-expand="onNodeExpand"
        placeholder="Wybierz krok questa"
        class="w-full md:w-80"
        selectionMode="multiple"
    />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: [String, Array],
    loading: Boolean,
    options: Array,
    onNodeExpand: Function,
    returnList: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const modelValueWrapper = computed(() => {
    if (!props.modelValue) return null

    if (Array.isArray(props.modelValue)) {
        // Convert array of values to object with keys
        const result = {}
        props.modelValue.forEach(key => {
            result[key] = true
        })
        return result
    } else {
        // Single value as before
        return { [props.modelValue]: true }
    }
})

function handleUpdate(val) {
    if (!val) {
        emit('update:modelValue', props.returnList ? [] : null)
        return
    }

    const keys = Object.keys(val)

    if (props.returnList) {
        // Return all selected keys as an array
        emit('update:modelValue', keys)
    } else {
        // Return only the first key as before
        const firstKey = keys.length > 0 ? keys[0] : null
        emit('update:modelValue', firstKey)
    }
}
</script>
