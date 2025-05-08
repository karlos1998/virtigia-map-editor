<script setup lang="ts">
import { ref } from 'vue';
import { NpcWithLocationResource } from '@/Resources/Npc.resource';

const props = defineProps<{
    npcs: NpcWithLocationResource[];
    scale: number;
    npcScale: boolean;
}>();

const emit = defineEmits<{
    (e: 'showNpcConfirmDialog', event: MouseEvent, npc: NpcWithLocationResource): void;
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
</script>

<template>
    <div
        v-for="npc in props.npcs"
        :key="`npc-${npc.id}-${props.npcScale}`"
        class="absolute npc"
        v-tip.npc="npc"
        @click="emit('showNpcConfirmDialog', $event, npc)"
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
</style>
