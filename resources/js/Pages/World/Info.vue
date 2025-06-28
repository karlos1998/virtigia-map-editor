<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import { computed } from 'vue';

defineProps<{
    worldName: string
    migrations: object[]
}>();

const getBatchColor = computed(() => {
    const batchColors = {};
    let colorIndex = 0;
    const colors = ['bg-blue-50', 'bg-green-50', 'bg-yellow-50',  'bg-indigo-50', 'bg-purple-50', 'bg-pink-50'];

    return (batch) => {
        if (!batch) return '';

        if (!batchColors[batch]) {
            batchColors[batch] = colors[colorIndex % colors.length];
            colorIndex++;
        }

        return batchColors[batch];
    };
});
</script>

<template>
    <AppLayout title="World Information">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                World Information: {{ worldName }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium mb-4">Migration Status</h3>
                        <p class="mb-4">This page shows the status of migrations for the current world. Migrations marked in green have been executed, while those in red have not. Executed migrations are grouped by batch number (migrations with the same background color were executed together).</p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Migration
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Batch
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="migration in migrations"
                                        :key="migration.name"
                                        :class="[migration.executed ? getBatchColor(migration.batch) : '']">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ migration.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span
                                                :class="migration.executed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ migration.executed ? 'Executed' : 'Not Executed' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ migration.batch ? migration.batch : '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
