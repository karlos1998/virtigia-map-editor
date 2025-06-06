<script setup>
import AppSidebar from '@/layout/AppSidebar.vue';
import { useLayout } from '@/layout/composables/layout';
import { usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import AppLogo from '@/Components/AppLogo.vue';
import UserAvatar from "@/layout/UserAvatar.vue";

const { isHorizontal, onMenuToggle, showConfigSidebar, showSidebar } = useLayout();

const user = computed(() => usePage().props.auth.user);
const world = computed(() => usePage().props.auth.world);

const worldOptions = ref([
    { label: 'Retro', value: 'retro' },
    { label: 'Classic', value: 'classic' },
    { label: 'Legacy', value: 'legacy' }
]);

const selectedWorld = ref(world.value);

const switchWorld = () => {
    router.post(route('switch-world'), {
        world: selectedWorld.value
    });
};
</script>

<template>
    <div class="layout-topbar">
        <div class="topbar-start">
            <Button ref="menubutton" type="button" class="topbar-menubutton p-trigger duration-300 bg-primary-600 hover:bg-primary-700 text-white" @click="onMenuToggle">
                <i class="pi pi-bars"></i>
            </Button>

            <div class="hidden md:flex items-center ml-4">
                <AppLogo />
                <Badge value="Editor" severity="info" class="ml-2"></Badge>
            </div>
        </div>

        <div class="layout-topbar-menu-section">
            <AppSidebar></AppSidebar>
        </div>

        <div class="topbar-end">
            <ul class="topbar-menu">
                <li class="hidden md:block mr-2">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search"></i>
                        <InputText type="text" placeholder="Szukaj..." class="p-inputtext-sm" />
                    </span>
                </li>

                <li class="md:hidden">
                    <Button type="button" icon="pi pi-search" rounded outlined severity="secondary" class="mr-2"></Button>
                </li>

                <li class="mr-2">
                    <div class="flex items-center">
                        <span class="mr-2 text-sm text-gray-600 dark:text-gray-300">World:</span>
                        <Dropdown
                            v-model="selectedWorld"
                            :options="worldOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="w-32"
                            @change="switchWorld"
                            aria-label="Select World"
                        />
                    </div>
                </li>

                <li class="topbar-item">
                    <a v-styleclass="{ selector: '@next', enterFromClass: '!hidden', enterActiveClass: 'animate-scalein', leaveToClass: '!hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: 'true' }" class="cursor-pointer flex items-center">
                        <UserAvatar />
                        <span class="ml-2 font-medium hidden md:inline">{{ user.name }}</span>
                        <i class="pi pi-chevron-down ml-2 text-xs"></i>
                    </a>
                    <ul class="!hidden topbar-menu active-topbar-menu !p-3 w-60 z-50 rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                        <li role="menuitem" class="!m-0 !mb-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                            <a href="#" class="flex items-center text-gray-700 dark:text-gray-200">
                                <i class="pi pi-fw pi-user mr-2 text-primary-500"></i>
                                <span>Profil</span>
                            </a>
                        </li>
                        <li role="menuitem" class="!m-0 !mb-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                            <a href="#" class="flex items-center text-gray-700 dark:text-gray-200">
                                <i class="pi pi-fw pi-cog mr-2 text-primary-500"></i>
                                <span>Ustawienia</span>
                            </a>
                        </li>
                        <li role="menuitem" class="!m-0 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                            <Link
                                :href="route('logout')"
                                method="post"
                                class="flex items-center text-gray-700 dark:text-gray-200"
                            >
                                <i class="pi pi-fw pi-sign-out mr-2 text-primary-500"></i>
                                <span>Wyloguj się</span>
                            </Link>
                        </li>
                    </ul>
                </li>

                <li>
                    <Button type="button" icon="pi pi-cog" rounded outlined severity="secondary" class="ml-2" @click="showConfigSidebar"></Button>
                </li>
            </ul>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.layout-topbar {
    @apply fixed top-0 left-0 w-full h-16 px-4 flex items-center justify-between bg-white dark:bg-gray-800 shadow-sm z-40 border-b border-gray-100 dark:border-gray-700;
}

.topbar-start {
    @apply flex items-center;
}

.topbar-end {
    @apply flex items-center;
}

:deep(.layout-horizontal) .topbar-end {
    @apply ml-auto px-4;
}

.topbar-menu {
    @apply flex items-center list-none p-0 m-0;
}

.topbar-item {
    @apply relative;
}

:deep(.p-button.p-button-icon-only) {
    @apply w-10 h-10;
}

:deep(.p-inputtext) {
    @apply py-1.5;
}
</style>
