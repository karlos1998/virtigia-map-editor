<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import type { NpcLocationResource } from '@/Resources/NpcLocation.resource';

const props = withDefaults(defineProps<{
    location: NpcLocationResource;
    npcSrc: string;
    compact?: boolean;
}>(), {
    compact: false,
});

const tileSize = 32;
const previewWidthTiles = 7;
const previewHeightTiles = 5;

const spriteWidth = ref(32);
const spriteHeight = ref(32);

const maxCropX = computed(() => Math.max(props.location.map_width - previewWidthTiles, 0));
const maxCropY = computed(() => Math.max(props.location.map_height - previewHeightTiles, 0));

const cropX = computed(() => {
    const centeredX = props.location.x - Math.floor(previewWidthTiles / 2);

    return Math.min(Math.max(centeredX, 0), maxCropX.value);
});

const cropY = computed(() => {
    const centeredY = props.location.y - Math.floor(previewHeightTiles / 2);

    return Math.min(Math.max(centeredY, 0), maxCropY.value);
});

const previewWidth = previewWidthTiles * tileSize;
const previewHeight = previewHeightTiles * tileSize;

const spriteLeft = computed(() => {
    return (props.location.x - cropX.value) * tileSize + (tileSize - spriteWidth.value) / 2;
});

const spriteTop = computed(() => {
    return (props.location.y - cropY.value) * tileSize - (spriteHeight.value - tileSize);
});

const handleSpriteLoad = (event: Event): void => {
    const image = event.target as HTMLImageElement;

    spriteWidth.value = image.naturalWidth || image.width || 32;
    spriteHeight.value = image.naturalHeight || image.height || 32;
};
</script>

<template>
    <div
        class="flex rounded-xl border border-surface-200 bg-surface-0 p-3 shadow-sm dark:border-surface-700 dark:bg-surface-900"
        :class="compact ? 'flex-col gap-3' : 'flex-col gap-4 md:flex-row md:items-center'"
    >
        <Link
            :href="route('maps.show', location.map_id)"
            class="block w-fit no-underline"
        >
            <div
                class="relative overflow-hidden rounded-lg border border-surface-300 bg-surface-100 dark:border-surface-600 dark:bg-surface-800"
                :style="{
                    width: `${previewWidth}px`,
                    height: `${previewHeight}px`,
                }"
            >
                <img
                    :src="location.map_src"
                    alt="Podglad mapy"
                    class="pointer-events-none absolute max-w-none select-none"
                    :style="{
                        left: `${-cropX * tileSize}px`,
                        top: `${-cropY * tileSize}px`,
                        width: `${location.map_width * tileSize}px`,
                        height: `${location.map_height * tileSize}px`,
                        imageRendering: 'pixelated',
                    }"
                />

                <div
                    class="absolute border border-red-500/70 bg-red-500/25"
                    :style="{
                        width: `${tileSize}px`,
                        height: `${tileSize}px`,
                        left: `${(location.x - cropX) * tileSize}px`,
                        top: `${(location.y - cropY) * tileSize}px`,
                    }"
                />

                <img
                    :src="npcSrc"
                    alt="NPC"
                    class="absolute"
                    :style="{
                        width: `${spriteWidth}px`,
                        height: `${spriteHeight}px`,
                        left: `${spriteLeft}px`,
                        top: `${spriteTop}px`,
                        imageRendering: 'pixelated',
                    }"
                    @load="handleSpriteLoad"
                />
            </div>
        </Link>

        <div class="flex min-w-0 flex-col gap-2 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <Tag severity="contrast" :value="`Mapa #${location.map_id}`" />
                <span class="font-semibold text-surface-900 dark:text-surface-0">
                    {{ location.map_name }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-surface-600 dark:text-surface-300">
                <Tag severity="info" :value="`X: ${location.x}`" />
                <Tag severity="info" :value="`Y: ${location.y}`" />
            </div>

            <div>
                <Link :href="route('maps.show', location.map_id)" class="no-underline">
                    <Button label="Pokaż mapę" size="small" />
                </Link>
            </div>
        </div>
    </div>
</template>
