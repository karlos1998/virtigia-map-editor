<script setup lang="ts">
import { router } from "@inertiajs/vue3";

defineProps<{
    routeBack: string
}>();
</script>

<template>
    <div class="card">
        <div class="flex justify-between p-4">
            <div class="font-medium text-3xl text-gray-900">
                <slot name="header" />
            </div>
            <div class="flex self-start">
                <button
                    class="px-4 py-2 text-white bg-blue-500 hover:bg-blue-600 rounded shadow mr-2"
                    @click="router.get(routeBack)"
                >
                    <i class="pi pi-arrow-left mr-2"></i>
                    Lista
                </button>
                <slot name="right-buttons" />
            </div>
        </div>
    </div>
</template>
