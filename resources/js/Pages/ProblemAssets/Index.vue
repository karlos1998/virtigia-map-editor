<template>
    <AppLayout>
        <div class="card">
            <h1 class="text-3xl font-bold mb-6">Błędy assetów dla świata: <span>{{ world }}</span></h1>
            <div v-if="loading" class="text-gray-500">Ładowanie...</div>
            <div v-else>
                <h2 class="text-xl mt-4 mb-2 font-semibold">Base Items</h2>
                <div v-if="items.length === 0" class="mb-4">Brak problemów z base items!</div>
                <table v-else class="min-w-full mb-8 border mt-2">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="px-2 py-2 border">ID</th>
                        <th class="px-2 py-2 border">Ścieżka</th>
                        <th class="px-2 py-2 border">Błąd</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in sortedItems" :key="item.id" class="hover:bg-red-50">
                        <td class="px-2 py-2 border">{{ item.id }}</td>
                        <td class="px-2 py-2 border font-mono">{{ item.src }}</td>
                        <td class="px-2 py-2 border text-red-600 font-semibold">{{ item.error }}</td>
                    </tr>
                    </tbody>
                </table>

                <h2 class="text-xl mt-8 mb-2 font-semibold">Base NPCs</h2>
                <div v-if="npcs.length === 0" class="mb-4">Brak problemów z base npcs!</div>
                <table v-else class="min-w-full border mt-2">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="px-2 py-2 border">ID</th>
                        <th class="px-2 py-2 border">Ścieżka</th>
                        <th class="px-2 py-2 border">Błąd</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="npc in sortedNpcs" :key="npc.id" class="hover:bg-red-50">
                        <td class="px-2 py-2 border">{{ npc.id }}</td>
                        <td class="px-2 py-2 border font-mono">{{ npc.src }}</td>
                        <td class="px-2 py-2 border text-red-600 font-semibold">{{ npc.error }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/layout/AppLayout.vue";
import {computed} from 'vue';

const props = defineProps({
    items: Array,
    npcs: Array,
    world: String,
    loading: Boolean
});

const sortedItems = computed(() => {
    return [...props.items].sort((a, b) => a.error.localeCompare(b.error));
});
const sortedNpcs = computed(() => {
    return [...props.npcs].sort((a, b) => a.error.localeCompare(b.error));
});
</script>

<style scoped>
table {
    border-collapse: collapse;
}

th, td {
    text-align: left;
}
</style>
