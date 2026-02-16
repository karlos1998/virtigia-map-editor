<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';
import {route} from "ziggy-js";
import { useLayout } from '@/layout/composables/layout';
// import { hasRole } from '@/roles.ts';

const { isHorizontal, isSlim, isSlimPlus } = useLayout();

const model = ref([
    {
        label: 'General',
        icon: 'pi pi-compass',
        items: [
            {
                label: 'Dashboard',
                icon: 'pi pi-fw pi-home',
                route: 'dashboard',
                badge: 'New',
                badgeClass: 'bg-accent-500 text-white',
            },
        ],
    },
    {
        label: 'Edycja',
        icon: 'pi pi-pencil',
        // hidden: !hasRole('system-admin'),
        items: [
            {
                label: 'Mapy',
                icon: 'pi pi-map',
                route: 'maps.index',
                badge: '5',
                badgeClass: 'bg-primary-500 text-white',
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
                badge: '12',
                badgeClass: 'bg-secondary-500 text-white',
            },
            {
                label: 'Dialogi Npc',
                icon: 'pi pi-comments',
                route: 'dialogs.index',
            },
            {
                label: 'Sklepy',
                icon: 'pi pi-shopping-cart',
                route: 'shops.index',
            },
            {
                label: 'Bazowe Przedmioty',
                icon: 'pi pi-box',
                route: 'base-items.index',
            },
            {
                label: 'Odnawialne Przedmioty',
                icon: 'pi pi-box',
                route: 'renewable-items.index',
            },
            {
                label: 'Questy',
                icon: 'pi pi-box',
                route: 'quests.index',
            },
            {
                label: 'Miejsca odrodzenia',
                icon: 'pi pi-box',
                route: 'respawn-points.index',
            },
            {
                label: 'Punkty startowe profesji',
                icon: 'pi pi-user-plus',
                route: 'spawn-points.index',
            },
            {
                label: 'Przejścia na tytanów',
                icon: 'pi pi-user-plus',
                route: 'titan-doors.index',
            },
            {
                label: 'Kalendarz nagród',
                icon: 'pi pi-calendar',
                route: 'calendar-days.index',
            },
            {
                label: 'Mapa Świata',
                icon: 'pi pi-map',
                route: 'maps.world',
            },
            {
                label: 'Ciosy Specjalne',
                icon: 'pi pi-map',
                route: 'special-attacks.index',
            },
            {
                label: 'Liczniki Dialogowe',
                icon: 'pi pi-hashtag',
                route: 'dialog-counters.index',
            },
        ],
    },
    {
        label: 'Zaawansowane',
        icon: 'pi pi-cog',
        items: [
            {
                label: 'Logi zmian',
                icon: 'pi pi-history',
                route: 'activity-logs.index',
            },
            {
                label: 'Informacje o świecie',
                icon: 'pi pi-info-circle',
                route: 'world-info.index',
            },
            {
                label: 'Użytkownicy',
                icon: 'pi pi-users',
                route: 'users.index',
            },
            {
                label: 'Problemy z assetami',
                icon: 'pi pi-exclamation-triangle',
                route: 'problem-assets.index',
            },
            {
                label: 'Batchy',
                icon: 'pi pi-play-circle',
                route: 'batches.index',
            },
        ],
    },
]);
</script>

<template>
    <div class="menu-wrapper" :class="{ 'horizontal': isHorizontal, 'slim': isSlim, 'slim-plus': isSlimPlus }">

        <ul class="layout-menu" :class="{ 'horizontal-menu': isHorizontal }">
            <template v-for="(item, i) in model" :key="item">
                <div class="menu-category" v-if="!isSlim && !isSlimPlus && !isHorizontal">
                    <span class="menu-category-label">{{ item.label }}</span>
                </div>

                <AppMenuItem :item="item" root :index="i" />

                <li class="menu-separator" v-if="i < model.length - 1 && !isHorizontal"></li>
            </template>
        </ul>
    </div>
</template>

<style lang="scss" scoped>
.menu-wrapper {
    @apply w-full;
}

.menu-wrapper.horizontal {
    @apply w-full flex flex-col items-center;
}

.menu-wrapper.slim,
.menu-wrapper.slim-plus {
    @apply w-full flex flex-col items-center;
}

.menu-search {
    @apply px-2;
}

.menu-category {
    @apply px-3 py-2;
}

.menu-category-label {
    @apply text-xs font-semibold uppercase text-gray-500 dark:text-gray-400;
}

.layout-menu {
    @apply list-none p-0 m-0;
}

.layout-menu.horizontal-menu {
    @apply flex flex-row flex-wrap justify-center gap-2;
}

.menu-separator {
    @apply border-t border-gray-100 dark:border-gray-700 my-2;
}

:deep(.p-inputtext) {
    @apply py-1.5 text-sm;
}

:deep(.layout-menuitem-root) {
    @apply mb-1;
}

:deep(.layout-menuitem-root > .layout-menuitem-link) {
    @apply rounded-lg transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-gray-700;
}

:deep(.layout-menuitem-icon) {
    @apply text-primary-500;
}

:deep(.layout-menuitem-text) {
    @apply font-medium;
}

:deep(.layout-submenu-toggler) {
    @apply text-gray-400;
}

:deep(.layout-menuitem-badge) {
    @apply rounded-full text-xs font-medium px-2 py-0.5 ml-auto;
}
</style>
