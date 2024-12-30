<script setup lang="ts">


import {onMounted, ref} from "vue";
import axios from "axios";
import {route} from "ziggy-js";

const src = defineModel<string>('src')

const onNodeExpand = (node) => {

    console.log('onNodeExpand2', node)

    if(node.children) {
        return;
    }

    node.loading = true;

    setTimeout(async () => {
        const items = await load(node.key);
        node.children = [];
        items.forEach((item) => {
            node.children.push(item);
        })
        node.loading = false;

    }, 100)
};

const nodes = ref();

type Item = {
    path: string
    type: 'file' | 'dir'
}

const load = async (path) => {
    const { data } = await axios.get<Item[]>(route('assets.search', {
        path: path
    }));

    return data.map((item) => {
        return {
            key: item.path,
            label: item.path,
            leaf: item.type == 'file',
            loading: false,
        }
    })
}

const init = async () => {
    nodes.value = await load('img/npc/retro');
}

onMounted(() => {
    init();
});

const emit = defineEmits(['selected']);

</script>
<template>
    <Tree :value="nodes" @node-expand="onNodeExpand" loadingMode="icon">
        <template #default="{ node }">
            <div class="flex items-center justify-center">
                <div v-if="node.leaf" class="flex items-center p-4  space-x-4 border border-gray-300 rounded-lg shadow-md bg-white">
                    <img
                        :src="`https://s3.letscode.it/virtigia-assets/${node.label}`"
                        alt="Grafika"
                        class="object-cover rounded-md"
                    />
                    <a
                        :href="`https://s3.letscode.it/virtigia-assets/${node.label}`"
                        target="_blank"
                        class="text-blue-500 underline text-center"
                    >
                        {{ node.label }}
                    </a>
                    <Button label="Wybierz" @click="src = node.label.replace('img/npc/', ''); emit('selected')" />
                </div>
                <div v-else class="text-gray-700 text-lg font-medium">
                    {{ node.label }}
                </div>
            </div>
        </template>

    </Tree>
</template>

<style scoped>

</style>
