<script setup>
import AppSidebar from '@/layout/AppSidebar.vue';
import { useLayout } from '@/layout/composables/layout';
import { usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import AppLogo from '@/Components/AppLogo.vue';
import UserAvatar from "@/layout/UserAvatar.vue";
import axios from 'axios';

const { isHorizontal, onMenuToggle, showConfigSidebar, showSidebar } = useLayout();

const user = computed(() => usePage().props.auth.user);
const world = computed(() => usePage().props.auth.world);

const worldOptions = ref([
    { label: 'Retro', value: 'retro' },
    { label: 'Classic', value: 'classic' },
    { label: 'Legacy', value: 'legacy' },
    { label: 'Demo', value: 'demo' },
]);

const selectedWorld = ref(world.value);

const switchWorld = () => {
    router.post(route('switch-world'), {
        world: selectedWorld.value
    });
};

// Search functionality
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchResults = ref(false);
const isSearching = ref(false);
const showMobileSearch = ref(false);
let searchTimeout = null;

const performSearch = () => {
    if (searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        showSearchResults.value = false;
        return;
    }

    isSearching.value = true;

    axios.get(route('search'), {
        params: {
            query: searchQuery.value
        }
    })
    .then(response => {
        searchResults.value = response.data;
        showSearchResults.value = true;
        isSearching.value = false;
    })
    .catch(error => {
        console.error('Search error:', error);
        isSearching.value = false;
    });
};

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch();
    }, 300);
};

const navigateToResult = (result) => {
    window.location.href = result.route;
    clearSearch();
};

const closeSearchResults = () => {
    showSearchResults.value = false;
};

const clearSearch = () => {
    searchQuery.value = '';
    showSearchResults.value = false;
    showMobileSearch.value = false;
};

// Close search results when clicking outside
const searchContainer = ref(null);

const handleClickOutside = (event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target)) {
        closeSearchResults();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
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
                <li class="hidden md:block mr-2 relative" ref="searchContainer">
                    <span class="p-input-icon-left">
                        <InputText
                            v-model="searchQuery"
                            @value-change="handleSearch"
                            type="text"
                            placeholder="Szukaj..."
                            class="p-inputtext-sm"
                        />
                    </span>

                    <!-- Search Results Dropdown -->
                    <div v-if="showSearchResults"
                         class="absolute mt-1 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700 z-50 max-h-96 overflow-y-auto">
                        <div class="p-3">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Wyniki wyszukiwania</h3>
                            <div v-if="isSearching" class="flex justify-center py-4">
                                <i class="pi pi-spin pi-spinner text-primary-500 text-xl"></i>
                            </div>
                            <ul v-else-if="searchResults.length > 0" class="space-y-1">
                                <li v-for="result in searchResults" :key="`${result.type}-${result.id}`"
                                    class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors duration-200"
                                    @click="navigateToResult(result)">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 mr-2">
                                            <i v-if="result.type === 'map'" class="pi pi-map text-blue-500"></i>
                                            <i v-else-if="result.type === 'baseitem'" class="pi pi-box text-green-500"></i>
                                            <i v-else-if="result.type === 'basenpc'" class="pi pi-user text-red-500"></i>
                                            <i v-else-if="result.type === 'dialog'" class="pi pi-comments text-purple-500"></i>
                                            <i v-else-if="result.type === 'quest'" class="pi pi-flag text-yellow-500"></i>
                                            <i v-else-if="result.type === 'shop'" class="pi pi-shopping-cart text-orange-500"></i>
                                            <i v-else class="pi pi-file text-gray-500"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ result.name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ result.type }}</div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <div v-else-if="searchQuery.trim().length >= 2" class="text-center text-gray-500 dark:text-gray-400 py-4">
                                Nie znaleziono wyników dla "{{ searchQuery }}"
                            </div>
                        </div>
                    </div>
                </li>

                <li class="md:hidden">
                    <Button type="button" icon="pi pi-search" rounded outlined severity="secondary" class="mr-2" @click="showMobileSearch = true"></Button>
                </li>

                <!-- Mobile Search Modal -->
                <Dialog v-model:visible="showMobileSearch" modal header="Wyszukiwanie" :style="{ width: '90%' }" @hide="clearSearch">
                    <div class="p-fluid">
                        <div class="p-field">
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-search"></i>
                                <InputText
                                    v-model="searchQuery"
                                    @input="handleSearch"
                                    type="text"
                                    placeholder="Szukaj..."
                                    class="w-full"
                                    autofocus
                                />
                            </span>
                        </div>

                        <div v-if="isSearching" class="flex justify-center py-4">
                            <i class="pi pi-spin pi-spinner text-primary-500 text-xl"></i>
                        </div>

                        <div v-else-if="searchResults.length > 0" class="mt-4">
                            <ul class="space-y-2">
                                <li v-for="result in searchResults" :key="`mobile-${result.type}-${result.id}`"
                                    class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors duration-200 border border-gray-100 dark:border-gray-700"
                                    @click="navigateToResult(result)">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 mr-3">
                                            <i v-if="result.type === 'map'" class="pi pi-map text-blue-500 text-lg"></i>
                                            <i v-else-if="result.type === 'baseitem'" class="pi pi-box text-green-500 text-lg"></i>
                                            <i v-else-if="result.type === 'basenpc'" class="pi pi-user text-red-500 text-lg"></i>
                                            <i v-else-if="result.type === 'dialog'" class="pi pi-comments text-purple-500 text-lg"></i>
                                            <i v-else-if="result.type === 'quest'" class="pi pi-flag text-yellow-500 text-lg"></i>
                                            <i v-else-if="result.type === 'shop'" class="pi pi-shopping-cart text-orange-500 text-lg"></i>
                                            <i v-else class="pi pi-file text-gray-500 text-lg"></i>
                                        </div>
                                        <div>
                                            <div class="text-base font-medium text-gray-700 dark:text-gray-300">{{ result.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ result.type }}</div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div v-else-if="searchQuery.trim().length >= 2" class="mt-4 text-center text-gray-500 dark:text-gray-400 py-4">
                            Nie znaleziono wyników dla "{{ searchQuery }}"
                        </div>
                    </div>

                    <template #footer>
                        <Button label="Zamknij" icon="pi pi-times" @click="showMobileSearch = false" class="p-button-text" />
                    </template>
                </Dialog>

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
