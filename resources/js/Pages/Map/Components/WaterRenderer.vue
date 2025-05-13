<script setup lang="ts">
import { computed } from 'vue';
import { MapResource } from '@/Resources/Map.resource';

const props = defineProps<{
    map: MapResource;
    scale: number;
}>();

// Parse water data and compute positions with depth
const waterPositions = computed(() => {
    if (!props.map.water) return [];

    const positions = [];
    const waterSegments = props.map.water.split('|');

    waterSegments.forEach(segment => {
        const [x1, x2, y, depth] = segment.split(',').map(Number);

        // For each x position in the range x1 to x2
        for (let x = x1; x <= x2; x++) {
            positions.push({
                x: x * 32,
                y: y * 32,
                depth: depth
            });
        }
    });

    return positions;
});

// Get color based on water depth
const getWaterColor = (depth: number) => {
    // Adjust color based on depth (1-8)
    const baseColor = '#3498db'; // Base blue color
    const opacity = Math.min(0.3 + (depth * 0.1), 0.9); // Deeper water is more opaque
    return `rgba(52, 152, 219, ${opacity})`;
};
</script>

<template>
    <div
        v-for="(pos, index) in waterPositions"
        :key="index"
        class="water"
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

.depth-indicator {
    color: white;
    font-weight: bold;
    font-size: 12px;
    text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.7);
}
</style>
