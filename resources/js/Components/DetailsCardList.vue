<script setup lang="ts">
defineProps<{
    title: string
}>()
</script>

<template>
    <div class="card">
        {{title}}
        <ul class="list-none p-0 m-0">
            <slot />
        </ul>
    </div>
</template>

<style scoped lang="scss">

</style>
