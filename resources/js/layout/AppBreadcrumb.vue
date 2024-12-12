<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { PageProps } from '@/types';

const items = computed(() => usePage<PageProps<{
    breadcrumbs: Array<{
        url: string,
        name: string,
    }>
}>>().props.breadcrumbs)
</script>


<template>
<!--    <nav class="layout-breadcrumb">-->
<!--        <ol>-->
<!--            <template v-for="(breadcrumbRoute, i) in items" :key="breadcrumbRoute">-->
<!--                <li>{{ breadcrumbRoute.name }}</li>-->
<!--                <li v-if="i !== items.length - 1" class="layout-breadcrumb-chevron">/</li>-->
<!--            </template>-->
<!--        </ol>-->
<!--    </nav>-->


    <div class="pb-4 text-xl leading-tight">
        <span v-for="(item, index) in items" :key="index">
            <Link v-if="item.url" :href="item.url" method="get" type="button" class="font-medium text-primary-300">
                {{ item.name }}
            </Link>
            <span v-if="index < items.length - 1" class="px-2 pi pi-chevron-right"> </span>
            <span v-else class="text-gray-500">{{ item.name }}</span>
        </span>
    </div>

</template>
