<script setup lang="ts">
import { computed } from 'vue';
import { MapResource } from '@/Resources/Map.resource';

const props = defineProps<{
    map: MapResource;
    scale: number;
    editColsOn: boolean;
}>();

const collisionPositions = computed(() => {
    const positions = [];
    const colArray = props.map.col.split('');
    colArray.forEach((val, index) => {
        if (val === '1') {
            const x = (index % props.map.x) * 32;
            const y = Math.floor(index / props.map.x) * 32;
            positions.push({ x, y });
        }
    });
    return positions;
});
</script>

<template>
    <div
        v-for="(pos, index) in collisionPositions"
        :key="index"
        class="col col-opacity"
        :class="{
            'edit-cols': editColsOn
        }"
        :style="{
            top: `${pos.y * scale}px`,
            left: `${pos.x * scale}px`,
            width: `${32 * scale}px`,
            height: `${32 * scale}px`
        }"
        @click.stop
    />
</template>

<style scoped>
.col {
    width: 32px;
    height: 32px;
    position: absolute;
    background-color: #ba5208;
    pointer-events: none;
}

.edit-cols {
    background-color: pink;
}

.col-opacity {
    opacity: 0.7;
}
</style>
