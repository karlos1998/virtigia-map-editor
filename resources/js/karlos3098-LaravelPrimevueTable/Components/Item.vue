<script setup lang="ts">
import {BaseItemResource, BaseItemWithPosition} from "@/Resources/BaseItem.resource";
import {ref} from "vue";

defineProps<{
    item: BaseItemWithPosition
}>()

const fullUrl = (itemImage: string) => `https://s3.letscode.it/virtigia-assets/img/${itemImage}`
// const fullUrl = (itemImage: string) => `${Variables.assetsUrl}/img/${itemImage}`

const width = ref(8); // ilosc w lini
const margin = ref(0);
</script>
<template>
    <div
        class="item"
        :style="{
            // 'background-image': `url(${fullUrl(item.src)})`,
            // 'top': `${Math.floor((item.position) / width) * (32 + margin)}px`,
            // 'left': `${(item.position % width) * (32 + margin)}px`,
        }"
    >
        <slot />
    </div>
</template>

<style scoped>
.item {
    position: absolute;
    width: 32px;
    height: 32px;
    cursor: pointer;
    background-size: cover;
    transition: transform 0.2s ease-in-out;
    box-shadow: none;
    background-color: rgba(100, 237, 226, 0.3);
}
</style>
