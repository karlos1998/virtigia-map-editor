<script setup>
import {useLayout} from '@/layout/composables/layout';
import {computed, onBeforeUnmount, onMounted} from 'vue';
import AppBreadcrumb from './AppBreadcrumb.vue';
import AppConfig from './AppConfig.vue';
import AppRightMenu from './AppRightMenu.vue';
import AppTopbar from './AppTopbar.vue';
import DynamicDialog from 'primevue/dynamicdialog';
import {usePage} from "@inertiajs/vue3";
import RockTip from "../RockTip/components/rockTip.vue";

const {watchSidebarActive, unbindOutsideClickListener, containerClass} = useLayout();

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

                <div class="card">
                    Edytujesz układ map <span class="font-bold">{{ world }}</span>
                </div>

                <AppBreadcrumb></AppBreadcrumb>

                <slot/>

            </div>
        </div>

        <AppRightMenu></AppRightMenu>

        <div class="layout-mask"></div>
    </div>
    <Toast></Toast>
    <DynamicDialog/>
</template>

<style lang="scss">

/**
primevue Message = TODO . nie wiadomo czemu height ustawil sie na 100%.
 */
.p-message-content {
    height: auto !important;
}
</style>
