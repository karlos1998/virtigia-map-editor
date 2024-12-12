<script setup>
import { useLayout } from '@/layout/composables/layout';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import AppBreadcrumb from './AppBreadcrumb.vue';
import AppConfig from './AppConfig.vue';
import AppRightMenu from './AppRightMenu.vue';
import AppTopbar from './AppTopbar.vue';

const { layoutConfig, layoutState, watchSidebarActive, unbindOutsideClickListener, containerClass } = useLayout();

onMounted(() => {
    watchSidebarActive();
});

onBeforeUnmount(() => {
    unbindOutsideClickListener();
});

</script>

<template>
    <div class="layout-container" :class="containerClass">
        <AppTopbar></AppTopbar>

        <AppConfig></AppConfig>

        <div class="layout-content-wrapper">
            <div class="layout-content">

                <AppBreadcrumb></AppBreadcrumb>

                <slot/>

            </div>
        </div>

        <AppRightMenu></AppRightMenu>

        <div class="layout-mask"></div>
    </div>
    <Toast></Toast>
</template>

<style lang="scss"></style>
