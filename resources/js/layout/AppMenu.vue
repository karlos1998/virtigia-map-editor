<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';
import {route} from "ziggy-js";
// import { hasRole } from '@/roles.ts';

const model = ref([
    {
        label: 'General',
        icon: 'pi pi-home',
        items: [
            {
                label: 'Dashboard',
                icon: 'pi pi-fw pi-home',
                route: 'dashboard',
            },
        ],
    },
    {
        label: 'Edycja',
        icon: 'pi pi-server',
        // hidden: !hasRole('system-admin'),
        items: [
            {
                label: 'Map',
                icon: 'pi pi-compass',
                route: 'maps.index',
            },
            // {
            //     label: 'Horizon',
            //     icon: 'pi pi-compass',
            //     url: route('horizon.index'),
            // },
            // {
            //     label: 'Pulse',
            //     icon: 'pi pi-compass',
            //     url: route('pulse'),
            // },
        ],
    },
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
