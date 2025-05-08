<script setup lang="ts">
const props = defineProps<{
    x: number;
    y: number;
}>();
</script>

<template>
    <div class="coordinates-card">
        <p>X: {{ props.x }}, Y: {{ props.y }}</p>
    </div>
</template>

<style scoped>
.coordinates-card {
    position: fixed;
    bottom: 10px;
    right: 10px;
    z-index: 1000;
    width: 150px;
    text-align: center;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #ddd;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
</style>
