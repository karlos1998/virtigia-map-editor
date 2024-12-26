<script setup>
import AppSidebar from '@/layout/AppSidebar.vue';
import { useLayout } from '@/layout/composables/layout';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";

const { isHorizontal, onMenuToggle, showConfigSidebar, showSidebar } = useLayout();

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <div class="layout-topbar">
        <div class="topbar-start">
            <Button ref="menubutton" type="button" class="topbar-menubutton p-trigger duration-300" @click="onMenuToggle">
                <i class="pi pi-bars"></i>
            </Button>
        </div>
        <div class="layout-topbar-menu-section">
            <AppSidebar></AppSidebar>
        </div>
        <div class="topbar-end">
            <ul class="topbar-menu">
<!--                <li :class="isHorizontal ? 'topbar-search hidden' : 'topbar-search hidden sm:block'">-->
<!--                    <IconField>-->
<!--                        <InputIcon class="pi pi-search" />-->
<!--                        <InputText type="text" placeholder="Search" class="w-48 sm:w-full" />-->
<!--                    </IconField>-->
<!--                </li>-->


                <li :class="isHorizontal ? 'block topbar-item' : 'block sm:!hidden topbar-item'">
                    <a v-styleclass="{ selector: '@next', enterFromClass: '!hidden', enterActiveClass: 'animate-scalein', leaveToClass: '!hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: 'true' }">
                        <Button type="button" icon="pi pi-search" text severity="secondary"></Button>
                    </a>
                    <ul class="!hidden topbar-menu active-topbar-menu !p-4 w-60 z-5" style="bottom: -5.8rem">
                        <IconField class="w-full">
                            <InputIcon class="pi pi-search" />
                            <InputText type="text" placeholder="Search" class="w-full" />
                        </IconField>
                    </ul>
                </li>


                <li class="topbar-item">
                    <a v-styleclass="{ selector: '@next', enterFromClass: '!hidden', enterActiveClass: 'animate-scalein', leaveToClass: '!hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: 'true' }" class="cursor-pointer">
                        <div class="avatar rounded-xl" :style="{
                            'background-image': `url(${user.src})`
                        }" />
                    </a>
                    <ul :class="'topbar-menu active-topbar-menu !p-6 w-60 z-50 !hidden'">
                        <li role="menuitem" class="!m-0 !mb-4">
                            <a
                                href="#"
                                class="flex items-center hover:text-primary-500 duration-200"
                                v-styleclass="{ selector: '@grandparent', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: 'true' }"
                            >
                                <i class="pi pi-fw pi-lock mr-2"></i>
                                <span>{{ user.name }}</span>
                            </a>
                        </li>
                        <li role="menuitem" class="!m-0 !mb-4">
<!--                            <Link-->
<!--                                :href="route('profile.edit')"-->
<!--                                class="flex items-center hover:text-primary-500 duration-200"-->
<!--                                v-styleclass="{ selector: '@grandparent', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: 'true' }"-->
<!--                            >-->
<!--                                <i class="pi pi-fw pi-cog mr-2"></i>-->
<!--                                <span>Ustawienia</span>-->
<!--                            </Link>-->
                        </li>
                        <li role="menuitem" class="!m-0">
                            <Link
                                :href="route('logout')"
                                method="post"
                                class="flex items-center hover:text-primary-500 duration-200"
                                v-styleclass="{ selector: '@grandparent', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: 'true' }"
                            >
                                <i class="pi pi-fw pi-sign-out mr-2"></i>
                                <span>Wyloguj się</span>
                            </Link>
                        </li>
                    </ul>
                </li>
                <li>
                    <Button type="button" icon="pi pi-cog" class="flex-shrink-0" text severity="secondary" @click="showConfigSidebar"></Button>
                </li>

<!--                <li>-->
<!--                    <Button type="button" icon="pi pi-arrow-left" class="flex-shrink-0" text severity="secondary" @click="showSidebar"></Button>-->
<!--                </li>-->
            </ul>
        </div>
    </div>
</template>

<style scoped>
.avatar {
    width: 32px;
    height:32px;
    background-repeat: no-repeat;
}
</style>
