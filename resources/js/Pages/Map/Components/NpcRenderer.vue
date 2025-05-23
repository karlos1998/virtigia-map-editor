<script setup lang="ts">
import { ref, computed } from 'vue';
import { NpcWithLocationResource } from '@/Resources/Npc.resource';

const props = defineProps<{
    npcs: NpcWithLocationResource[];
    scale: number;
    npcScale: boolean;
    addToGroupMode: boolean;
    sourceNpc: NpcWithLocationResource | null;
}>();

const emit = defineEmits<{
    (e: 'showNpcConfirmDialog', event: MouseEvent, npc: NpcWithLocationResource): void;
    (e: 'addToGroup', npc: NpcWithLocationResource): void;
}>();

const npcWidths = ref<Record<string, number>>({});
const npcHeights = ref<Record<string, number>>({});

const adjustNpcOffset = (id: string, element: HTMLImageElement) => {
    npcWidths.value[id] = element.width;
    npcHeights.value[id] = element.height;
};

const groupColors = [
    '#00FFFF', '#FFFF00', '#FF00FF', '#FFD700', '#ADFF2F',
    '#87CEFA', '#FF69B4', '#BA55D3', '#7FFFD4', '#FFA07A',
    '#E0FFFF', '#F0E68C', '#D8BFD8', '#FFE4B5', '#B0E0E6',
    '#FFB6C1', '#AFEEEE', '#F5DEB3', '#DDA0DD', '#F4A460'
];

const getGroupColor = (groupId) => groupColors[groupId % groupColors.length];

// Compute which NPCs are nearby the source NPC (within 5 tiles)
const nearbyNpcs = computed(() => {
    if (!props.addToGroupMode || !props.sourceNpc) return {};

    const result = {};
    props.npcs.forEach(npc => {
        // Skip the source NPC itself
        if (npc.id === props.sourceNpc.id) return;

        // Skip NPCs that are already in the same group as the source NPC
        if (props.sourceNpc.group_id && npc.group_id === props.sourceNpc.group_id) return;

        // Check if the NPC is within 5 tiles of the source NPC
        const dx = Math.abs(npc.location.x - props.sourceNpc.location.x);
        const dy = Math.abs(npc.location.y - props.sourceNpc.location.y);

        if (dx <= 5 && dy <= 5) {
            result[npc.id] = true;
        }
    });

    return result;
});

// Handle NPC click based on current mode
const handleNpcClick = (event: MouseEvent, npc: NpcWithLocationResource) => {
    if (props.addToGroupMode && nearbyNpcs.value[npc.id]) {
        emit('addToGroup', npc);
    } else {
        emit('showNpcConfirmDialog', event, npc);
    }
};
</script>

<template>
    <div
        v-for="npc in props.npcs"
        :key="`npc-${npc.id}-${props.npcScale}`"
        class="absolute npc"
        v-tip.npc="npc"
        @click="handleNpcClick($event, npc)"
        :class="{
            'nearby-npc': props.addToGroupMode && nearbyNpcs[npc.id],
            'source-npc': props.addToGroupMode && props.sourceNpc && npc.id === props.sourceNpc.id
        }"
        :style="{
            top: `${(npc.location.y * 32 - ((npcHeights[npc.id] ?? 32) - 32)) * props.scale}px`,
            left: `${npc.location.x * 32 * props.scale}px`,
            width: props.npcScale ? `${(npcWidths[npc.id] ?? 32) * props.scale}px` : undefined,
            height: props.npcScale ? `${(npcHeights[npc.id] ?? 32) * props.scale}px` : undefined,
        }"
    >
        <!-- Czerwony kwadrat u podstawy -->
        <div
            class="npc-footer"
            :style="{
                width: `${32 * props.scale}px`,
                height: `${32 * props.scale}px`,
                bottom: 0,
            }"
        />
        <!-- Obrazek NPC -->
        <img
            :src="npc.src"
            :style="{
                position: 'relative',
                width: `${npcWidths[npc.id] * props.scale}px`,
                bottom: 0,
                zIndex: 1,
                left: (32 - npcWidths[npc.id]) * props.scale / 2,
                border: npc.group_id !== null ? `4px dashed ${getGroupColor(npc.group_id)}` : 'none',
                borderRadius: npc.group_id !== null ? '8px' : '',
                boxShadow: npc.group_id !== null ? `0 0 10px ${getGroupColor(npc.group_id)}` : ''
            }"
            @load="adjustNpcOffset(npc.id, $event.target as HTMLImageElement)"
            alt="npc"
        />
    </div>
</template>

<style scoped>
.npc {
    position: absolute;
}

.group-border {
    border: #777bf1 dashed 3px;
}

.npc-footer {
    position: absolute;
    background-color: red;
}

.nearby-npc {
    cursor: pointer;
}

.nearby-npc img {
    border: 3px dashed #4CAF50 !important;
    border-radius: 8px;
    box-shadow: 0 0 10px #4CAF50;
    animation: pulse 1.5s infinite;
}

.source-npc img {
    border: 3px solid #FF5722 !important;
    border-radius: 8px;
    box-shadow: 0 0 15px #FF5722;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
    }
}
</style>
