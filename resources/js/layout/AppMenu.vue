<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';
// import { hasRole } from '@/roles.ts';

const model = ref([
    {
        label: 'General',
        icon: 'pi pi-home',
        items: [
            // {
            //     label: 'Dashboard',
            //     icon: 'pi pi-fw pi-home',
            //     route: 'dashboard',
            // },
        ],
    },
    // {
    //     label: 'Admin',
    //     icon: 'pi pi-server',
    //     hidden: !hasRole('system-admin'),
    //     items: [
    //         {
    //             label: 'Telescope',
    //             icon: 'pi pi-compass',
    //             url: route('telescope'),
    //         },
    //         {
    //             label: 'Horizon',
    //             icon: 'pi pi-compass',
    //             url: route('horizon.index'),
    //         },
    //         {
    //             label: 'Pulse',
    //             icon: 'pi pi-compass',
    //             url: route('pulse'),
    //         },
    //     ],
    // },
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <AppMenuItem :item="item" root :index="i" />

            <li class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
