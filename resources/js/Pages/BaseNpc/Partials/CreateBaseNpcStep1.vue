<script setup lang="ts">


import {computed, onMounted, ref} from "vue";
import axios from "axios";
import {route} from "ziggy-js";
import {usePage} from "@inertiajs/vue3";

const src = defineModel<string>('src')

const onNodeExpand = (node) => {

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
    const { data } = await axios.get<Item[]>(route('assets.base-npcs.search', {
        path: 'img/npc/' + path.split('img/npc/')[1],
        only_unused: onlyUnused.value,
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

const world = computed(() => usePage().props.auth.world);

const init = async () => {
    nodes.value = await load('img/npc/' + world.value);
}

onMounted(() => {
    init();
});

const rebuild = () => {
    nodes.value = null;
    init();
}

const emit = defineEmits(['selected']);

const onlyUnused = ref(true);

</script>
<template>

    <div class="flex items-center gap-2">
        <Checkbox v-model="onlyUnused" inputId="onlyUnused" name="onlyUnused" binary @change="rebuild" />
        <label for="onlyUnused">Pokaż tylko nieużwane grafiki</label>
    </div>

    <Tree v-if="nodes" :value="nodes" @node-expand="onNodeExpand" loadingMode="icon">
        <template #default="{ node }">
            <div class="flex items-center justify-center">
                <div v-if="node.leaf" class="flex items-center p-4  space-x-4 border border-gray-300 rounded-lg shadow-md bg-white">
                    <img
                        :src="node.label"
                        alt="Grafika"
                        class="object-cover rounded-md"
                    />
                    <a
                        :href="node.label"
                        target="_blank"
                        class="text-blue-500 underline text-center"
                    >
                        {{ node.label }}
                    </a>
                    <Button label="Wybierz" @click="src = node.label.split('img/npc/')[1]; emit('selected')" />
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
