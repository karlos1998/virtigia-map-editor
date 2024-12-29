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
                label: 'Mapy',
                icon: 'pi pi-map',
                route: 'maps.index',
            },
            {
                label: 'Bazowe Npc',
                icon: 'pi pi-user',
                route: 'base-npcs.index',
            },
            {
                label: 'Rozmieszczone Npc',
                icon: 'pi pi-users',
                route: 'npcs.index',
            },
            {
                label: 'Dialogi Npc',
                icon: 'pi pi-sitemap',
                route: 'dialogs.index',
            },
            {
                label: 'Sklepy',
                icon: 'pi pi-shop',
                route: 'shops.index',
            },
            {
                label: 'Bazowe Przedmioty',
                icon: 'pi pi-shopping-bag',
                route: 'base-items.index',
            },
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
