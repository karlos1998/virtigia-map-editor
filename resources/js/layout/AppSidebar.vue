<script setup>
import AppMenu from './AppMenu.vue';
import { useLayout } from '@/layout/composables/layout';
import AppLogo from '@/Components/AppLogo.vue';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const { layoutState, layoutConfig, onSidebarToggle, onAnchorToggle, isHorizontal, isSlim, isSlimPlus } = useLayout();

let timeout = null;

function onMouseEnter() {
    if (!layoutState.anchored) {
        if (timeout) {
            clearTimeout(timeout);
            timeout = null;
        }
        onSidebarToggle(true);
    }
}

function onMouseLeave() {
    if (!layoutState.anchored) {
        if (!timeout) {
            timeout = setTimeout(() => onSidebarToggle(false), 300);
        }
    }
}

function navigateToHome() {
    router.get('/');
}

const isReveal = computed(() => layoutConfig.menuMode === 'reveal');
const showLogoText = computed(() => !isSlim.value && !isSlimPlus.value);

</script>

<template>
    <div class="layout-sidebar" @mouseenter="onMouseEnter()" @mouseleave="onMouseLeave()">
        <div class="sidebar-header">
            <a @click="navigateToHome" class="app-logo cursor-pointer">
                <AppLogo :showText="showLogoText" />
            </a>
            <button class="layout-sidebar-anchor" type="button" @click="onAnchorToggle">
                <i class="pi pi-lock-open" v-if="!layoutState.anchored"></i>
                <i class="pi pi-lock" v-else></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="user-status">
                <div class="status-indicator online"></div>
                <span>Online</span>
            </div>
        </div>

        <div ref="menuContainer" class="layout-menu-container">
            <AppMenu></AppMenu>
        </div>

        <div class="sidebar-footer">
            <div class="version">v1.0.0</div>
            <div class="copyright">© 2025 Virtigia</div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.layout-sidebar {
    @apply fixed left-0 top-0 h-full bg-white dark:bg-gray-800 shadow-md z-50 flex flex-col transition-all duration-300 ease-in-out border-r border-gray-100 dark:border-gray-700 pt-16;
    width: 280px;
}

:deep(.layout-horizontal) .layout-sidebar {
    @apply fixed top-16 left-0 right-0 h-auto w-full border-r-0 border-b border-gray-100 dark:border-gray-700 pt-0;
    z-index: 39;
}

:deep(.layout-slim) .layout-sidebar,
:deep(.layout-slim-plus) .layout-sidebar {
    width: 80px;
}

.sidebar-header {
    @apply flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700;
}

:deep(.layout-slim) .sidebar-header,
:deep(.layout-slim-plus) .sidebar-header {
    @apply justify-center px-2;
}

:deep(.layout-horizontal) .sidebar-header {
    @apply hidden;
}

.logo-container {
    @apply flex items-center;
}

.logo-icon {
    @apply flex items-center justify-center w-10 h-10 rounded-lg bg-primary-500 text-white mr-3;
}

.logo-text {
    @apply flex flex-col;
}

.logo-name {
    @apply font-bold text-gray-800 dark:text-white text-lg;
}

.logo-subtitle {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.layout-sidebar-anchor {
    @apply w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200;
}

.sidebar-user {
    @apply px-4 py-3 border-b border-gray-100 dark:border-gray-700;
}

:deep(.layout-slim) .sidebar-user,
:deep(.layout-slim-plus) .sidebar-user {
    @apply px-2 py-2 flex justify-center;
}

:deep(.layout-horizontal) .sidebar-user {
    @apply hidden;
}

.user-status {
    @apply flex items-center;
}

:deep(.layout-slim) .user-status span,
:deep(.layout-slim-plus) .user-status span {
    @apply hidden;
}

.status-indicator {
    @apply w-2 h-2 rounded-full mr-2;

    &.online {
        @apply bg-green-500;
    }

    &.offline {
        @apply bg-gray-400;
    }
}

.layout-menu-container {
    @apply flex-grow overflow-y-auto px-3 py-4;
}

:deep(.layout-slim) .layout-menu-container,
:deep(.layout-slim-plus) .layout-menu-container {
    @apply px-1 py-2;
}

:deep(.layout-horizontal) .layout-menu-container {
    @apply flex flex-row justify-center px-4 py-2;
}

.sidebar-footer {
    @apply px-4 py-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex flex-col items-center;
}

:deep(.layout-slim) .sidebar-footer,
:deep(.layout-slim-plus) .sidebar-footer {
    @apply px-2 py-2;
}

:deep(.layout-horizontal) .sidebar-footer {
    @apply hidden;
}

.version {
    @apply mb-1;
}
</style>
