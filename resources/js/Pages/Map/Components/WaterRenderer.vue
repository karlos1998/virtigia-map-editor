<script setup lang="ts">
import { computed } from 'vue';
import { MapResource } from '@/Resources/Map.resource';

const props = defineProps<{
    map: MapResource;
    scale: number;
}>();

const waterPositions = computed(() => {
    if (!props.map.water) return [];

    const positions = [];
    const waterSegments = props.map.water.split('|');

    waterSegments.forEach(segment => {
        const [x1, x2, y, depth] = segment.split(',').map(Number);

        for (let x = x1; x <= x2; x++) {
            const tileIndex = y * props.map.x + x;
            positions.push({
                tileIndex,
                x: x * 32,
                y: y * 32,
                depth,
            });
        }
    });

    return positions;
});

const getWaterColor = (depth: number) => {
    const opacity = Math.min(0.3 + (depth * 0.1), 0.9);
    return `rgba(52, 152, 219, ${opacity})`;
};
</script>

<template>
    <div
        v-for="(pos, index) in waterPositions"
        :key="index"
        class="water"
        :class="{ 'water-with-collision': map.col[pos.tileIndex] === '1' }"
        :style="{
            top: `${pos.y * scale}px`,
            left: `${pos.x * scale}px`,
            width: `${32 * scale}px`,
            height: `${32 * scale}px`,
            backgroundColor: getWaterColor(pos.depth)
        }"
    >
        <div class="depth-indicator">{{ pos.depth }}</div>
    </div>
</template>

<style scoped>
.water {
    position: absolute;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.water-with-collision {
    background-image: repeating-linear-gradient(
        135deg,
        transparent 0 4px,
        #2b1b14 4px 5px,
        #ffc45e 5px 8px,
        #2b1b14 8px 9px
    );
    box-shadow: inset 0 0 0 1px #ffc45e;
}

.water-with-collision .depth-indicator {
    padding: 0 2px;
    border-radius: 3px;
    background: #172435;
    line-height: 1;
}

.depth-indicator {
    color: white;
    font-weight: bold;
    font-size: 12px;
    text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.7);
}
</style>
