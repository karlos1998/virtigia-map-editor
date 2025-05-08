<script setup lang="ts">
import { DoorResource } from '@/Resources/Door.resource';

const props = defineProps<{
    doors: DoorResource[];
    scale: number;
}>();

const emit = defineEmits<{
    (e: 'showDoorConfirmDialog', event: MouseEvent, door: DoorResource): void;
}>();
</script>

<template>
    <div
        class="door"
        v-for="door in props.doors"
        v-tooltip="'Przejście do: ' + door.name + ' (' + door.go_x + ',' + door.go_y + '), \nPowrót: ' + (door.double_sided ? 'Tak' : 'Nie' ) +
        (door.min_lvl !== null ? '\nMinimalny poziom: ' + door.min_lvl : '') +
        (door.max_lvl !== null ? '\nMaksymalny poziom: ' + door.max_lvl : '') +
        (door.required_base_item ? '\nWymagany przedmiot: ' + door.required_base_item.name : '')"
        :style="{
            width: `${32 * props.scale}px`,
            height: `${32 * props.scale}px`,
            top: `${door.y * 32 * props.scale}px`,
            left: `${door.x * 32 * props.scale}px`,
        }"
        :class="{'double-sided': door.double_sided}"
        @click="emit('showDoorConfirmDialog', $event, door)"
    />
</template>

<style scoped>
.door {
    width: 32px;
    height: 32px;
    position: absolute;
    background-color: #353030;
}

.double-sided {
    background-color: #000000 !important;
    /* border: 6px black solid; */
}
</style>
