<script setup>
import AppMenu from './AppMenu.vue';
import { useLayout } from '@/layout/composables/layout';
// import AppLogoNormal from '@/Components/AppLogoNormal.vue';
// import AppLogoSingle from '@/Components/AppLogoSingle.vue';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const { layoutState, layoutConfig, onSidebarToggle, onAnchorToggle } = useLayout();

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

</script>

<template>
    <div class="layout-sidebar" @mouseenter="onMouseEnter()" @mouseleave="onMouseLeave()">
        <div class="sidebar-header">
            <a @click="navigateToHome" class="app-logo cursor-pointer">

<!--                <AppLogoNormal  />-->

<!--                <AppLogoSingle :class="{ '-mr-2' : isReveal}" :width="isReveal ? 30 : 60" />-->

            </a>
            <button class="layout-sidebar-anchor" type="button" @click="onAnchorToggle"></button>
        </div>

        <div ref="menuContainer" class="layout-menu-container">
            <AppMenu></AppMenu>
        </div>
    </div>
</template>

<style lang="scss" scoped></style>
