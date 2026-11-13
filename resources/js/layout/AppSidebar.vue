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
            timeout = setTimeout(() => {
                timeout = null;

                if (!layoutState.anchored) {
                    onSidebarToggle(false);
                }
            }, 300);
        }
    }
}

function toggleAnchor() {
    if (timeout) {
        clearTimeout(timeout);
        timeout = null;
    }

    onAnchorToggle();
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
            <button class="layout-sidebar-anchor" type="button" @click.stop="toggleAnchor">
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
    @apply fixed left-0 top-0 h-full shadow-md z-50 flex flex-col transition-all duration-300 ease-in-out border-r pt-16;
    --logo-color: var(--menuitem-text-color);
    --logo-muted-color: var(--root-menuitem-text-color);
    width: 280px;
    background: var(--menu-bg);
    color: var(--menuitem-text-color);
}

:deep(.layout-horizontal) .layout-sidebar {
    @apply fixed top-16 left-0 right-0 h-auto w-full border-r-0 border-b pt-0;
    z-index: 39;
}

:deep(.layout-slim) .layout-sidebar,
:deep(.layout-slim-plus) .layout-sidebar {
    width: 80px;
}

.layout-sidebar,
.sidebar-header,
.sidebar-user,
.sidebar-footer {
    border-color: var(--menu-border-color);
}

.sidebar-header {
    @apply flex items-center justify-between px-4 py-3 border-b;
}

:deep(.layout-slim) .sidebar-header,
:deep(.layout-slim-plus) .sidebar-header {
    @apply justify-center px-2;
}

:deep(.layout-horizontal) .sidebar-header {
    @apply hidden;
}

.layout-sidebar-anchor {
    @apply w-8 h-8 flex items-center justify-center rounded-full transition-colors duration-200;
    background: var(--menuitem-hover-bg-color);
    color: var(--menuitem-text-color);
}

.sidebar-user {
    @apply px-4 py-3 border-b;
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
    @apply px-4 py-3 border-t text-xs flex flex-col items-center;
    color: var(--root-menuitem-text-color);
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
