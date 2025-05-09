<script setup>
import {useLayout} from '@/layout/composables/layout';
import {computed, onBeforeUnmount, onMounted} from 'vue';
import AppBreadcrumb from './AppBreadcrumb.vue';
import AppConfig from './AppConfig.vue';
import AppRightMenu from './AppRightMenu.vue';
import AppTopbar from './AppTopbar.vue';
import DynamicDialog from 'primevue/dynamicdialog';
import {usePage} from "@inertiajs/vue3";
import {RockTip} from "virtigia-tips";

const {watchSidebarActive, unbindOutsideClickListener, containerClass, onMenuToggle} = useLayout();

onMounted(() => {
    watchSidebarActive();
});

onBeforeUnmount(() => {
    unbindOutsideClickListener();
});

const world = computed(() => usePage().props.auth.world);
</script>

<template>
    <div class="layout-container" :class="containerClass">
        <AppTopbar></AppTopbar>

        <AppConfig></AppConfig>

        <RockTip />

        <div class="layout-content-wrapper">
            <div class="layout-content">
                <div class="card mb-4 bg-gradient-to-r from-primary-500 to-primary-700 text-white">
                    <div class="flex items-center">
                        <i class="pi pi-map-marker text-2xl mr-3"></i>
                        <div>
                            <h2 class="text-xl font-bold text-white">Edytujesz układ map</h2>
                            <p class="text-primary-100">Aktualny świat: <span class="font-bold text-white">{{ world }}</span></p>
                        </div>
                    </div>
                </div>

                <AppBreadcrumb class="mb-4"></AppBreadcrumb>

                <div class="transition-all duration-300 ease-in-out">
                    <slot/>
                </div>
            </div>
        </div>

        <AppRightMenu></AppRightMenu>

        <div class="layout-mask" @click="onMenuToggle"></div>
    </div>
    <Toast position="bottom-right"></Toast>
    <DynamicDialog/>
</template>

<style lang="scss">
/**
primevue Message = TODO . nie wiadomo czemu height ustawil sie na 100%.
 */
.p-message-content {
    height: auto !important;
}

.layout-container {
    @apply min-h-screen;
}

.layout-content-wrapper {
    @apply pt-20 md:pt-16 px-4 md:px-6 lg:px-8 transition-all duration-300 ease-in-out;
}

::deep(.layout-static.layout-sidebar-anchored) .layout-content-wrapper {
    @apply md:ml-[280px];
}

::deep(.layout-static) .layout-content-wrapper {
    @apply md:ml-[280px];
}

.layout-content {
    @apply max-w-7xl mx-auto py-6 pl-4;
}

.layout-mask {
    display: none;
    position: fixed;
    top: 4rem; /* Start below the topbar (16 = 4rem) */
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 30; /* Lower than topbar's z-index of 40 */
    background-color: var(--maskbg);
}

::deep(.layout-mobile-active) .layout-mask {
    @apply block;
}

.p-breadcrumb {
    @apply bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3;
}

.p-toast {
    @apply opacity-95;
}

.p-card {
    @apply rounded-xl shadow-card transition-all duration-300 hover:shadow-card-hover;
}

.p-button {
    @apply rounded-lg;
}

.p-inputtext {
    @apply rounded-lg;
}

.p-dialog {
    @apply rounded-xl overflow-hidden;
}

.p-tabview-nav {
    @apply border-b border-gray-200 dark:border-gray-700;
}

.p-tabview-selected {
    @apply border-primary-500 text-primary-700 dark:text-primary-300;
}

.p-datatable {
    @apply rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700;
}
</style>
