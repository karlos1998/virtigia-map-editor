<template>
    <AppLayout>
        <div class="card">
            <h1 class="text-3xl font-bold mb-6">Batchy zadań</h1>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border">
                    <div class="text-2xl font-bold text-blue-600">{{ stats?.total || 0 }}</div>
                    <div class="text-sm text-gray-600">Razem</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border">
                    <div class="text-2xl font-bold text-green-600">{{ stats?.finished || 0 }}</div>
                    <div class="text-sm text-gray-600">Zakończone</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg border">
                    <div class="text-2xl font-bold text-blue-600 animate-pulse">{{ stats?.running || 0 }}</div>
                    <div class="text-sm text-gray-600">W trakcie</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg border">
                    <div class="text-2xl font-bold text-red-600">{{ stats?.cancelled || 0 }}</div>
                    <div class="text-sm text-gray-600">Anulowane</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg border">
                    <div class="text-2xl font-bold text-yellow-600">{{ stats?.with_errors || 0 }}</div>
                    <div class="text-sm text-gray-600">Z błędami</div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Szukaj po nazwie</label>
                        <InputText
                            v-model="filters.search"
                            placeholder="Wpisz nazwę batcha..."
                            class="w-full"
                            @input="debouncedSearch"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <Dropdown
                            v-model="filters.status"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Wszystkie statusy"
                            class="w-full"
                            show-clear
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data od</label>
                        <Calendar
                            v-model="filters.date_from"
                            date-format="yy-mm-dd"
                            placeholder="Wybierz datę"
                            class="w-full"
                            show-icon
                            @date-select="applyFilters"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do</label>
                        <Calendar
                            v-model="filters.date_to"
                            date-format="yy-mm-dd"
                            placeholder="Wybierz datę"
                            class="w-full"
                            show-icon
                            @date-select="applyFilters"
                        />
                    </div>
                </div>

                <!-- Progress Range -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Postęp min (%)</label>
                        <InputNumber
                            v-model="filters.progress_min"
                            :min="0"
                            :max="100"
                            placeholder="0"
                            class="w-full"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Postęp max (%)</label>
                        <InputNumber
                            v-model="filters.progress_max"
                            :min="0"
                            :max="100"
                            placeholder="100"
                            class="w-full"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="flex items-end">
                        <Button
                            label="Wyczyść filtry"
                            icon="pi pi-filter-slash"
                            class="p-button-outlined p-button-secondary"
                            @click="clearFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Results Info -->
            <div class="flex justify-between items-center mb-4">
                <div class="text-sm text-gray-600">
                    Wyświetlanie {{ batches?.from || 0 }}-{{ batches?.to || 0 }} z {{ batches?.total || 0 }} batchy
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-700">Na stronę:</label>
                    <Dropdown
                        v-model="perPage"
                        :options="perPageOptions"
                        class="w-20"
                        @change="changePerPage"
                    />
                </div>
            </div>

            <!-- Batches List -->
            <div v-if="!batches?.data || batches.data.length === 0" class="text-gray-500 text-center py-8">
                Brak batchy spełniających kryteria wyszukiwania
            </div>

            <div v-else class="space-y-4">
                <div v-for="batch in batches.data" :key="batch.id"
                     class="border rounded-lg p-6 bg-white shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h2 class="text-lg font-semibold text-gray-900">{{ batch.name }}</h2>
                                <span v-if="batch.has_errors"
                                      class="inline-block px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                    <i class="pi pi-exclamation-triangle mr-1"></i>Błędy
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">ID: {{ batch.id }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                :class="getStatusBadgeClass(batch.status)"
                                class="inline-block px-3 py-1 rounded-full text-xs font-medium"
                            >
                                {{ getStatusText(batch.status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Postęp: {{ batch.completed_jobs }} / {{ batch.total_jobs }} zadań</span>
                            <span>{{ batch.progress }}%</span>
                        </div>
                        <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                            <div
                                :style="{ width: batch.progress + '%' }"
                                :class="getProgressBarClass(batch.status)"
                                class="h-full transition-all duration-500 ease-out"
                            ></div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="grid grid-cols-4 gap-3 mb-4 text-center">
                        <div>
                            <div class="text-lg font-bold text-blue-600">{{ batch.total_jobs }}</div>
                            <div class="text-xs text-gray-500">Razem</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-green-600">{{ batch.completed_jobs }}</div>
                            <div class="text-xs text-gray-500">Ukończone</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-yellow-600">{{ batch.pending_jobs }}</div>
                            <div class="text-xs text-gray-500">Oczekujące</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-red-600">{{ batch.failed_jobs }}</div>
                            <div class="text-xs text-gray-500">Błędne</div>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="text-sm text-gray-600 grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div><strong>Utworzony:</strong> {{ formatTimestamp(batch.created_at) }}</div>
                        <div v-if="batch.finished_at">
                            <strong>Zakończony:</strong> {{ formatTimestamp(batch.finished_at) }}
                        </div>
                        <div v-if="batch.cancelled_at">
                            <strong>Anulowany:</strong> {{ formatTimestamp(batch.cancelled_at) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-center">
                <Paginator
                    v-model:first="currentPage"
                    :rows="batches?.per_page || 15"
                    :total-records="batches?.total || 0"
                    :rows-per-page-options="perPageOptions"
                    @page="onPageChange"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/layout/AppLayout.vue";
import {ref, onMounted, watch} from 'vue';
import {router} from '@inertiajs/vue3';
import {route} from 'ziggy-js';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Paginator from 'primevue/paginator';

// Simple debounce function
const debounce = (func, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(null, args), delay);
    };
};

const props = defineProps({
    batches: {
        type: Object,
        default: () => ({
            data: [],
            total: 0,
            from: 0,
            to: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1
        })
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            finished: 0,
            running: 0,
            cancelled: 0,
            with_errors: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// Reactive data
const currentFilters = ref({...props.filters});
const perPage = ref(props.batches?.per_page || 15);
const currentPage = ref(props.batches?.current_page ? (props.batches.current_page - 1) * props.batches.per_page : 0);

// Options
const statusOptions = [
    {label: 'Wszystkie', value: ''},
    {label: 'Zakończone', value: 'finished'},
    {label: 'W trakcie', value: 'running'},
    {label: 'Anulowane', value: 'cancelled'},
    {label: 'Z błędami', value: 'with_errors'},
];

const perPageOptions = [10, 15, 25, 50, 100];

// Computed filters
const filters = ref({
    search: currentFilters.value.search || '',
    status: currentFilters.value.status || '',
    date_from: currentFilters.value.date_from ? new Date(currentFilters.value.date_from) : null,
    date_to: currentFilters.value.date_to ? new Date(currentFilters.value.date_to) : null,
    progress_min: currentFilters.value.progress_min || null,
    progress_max: currentFilters.value.progress_max || null,
    sort_by: currentFilters.value.sort_by || 'created_at',
    sort_direction: currentFilters.value.sort_direction || 'desc',
});

// Methods
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'finished':
            return 'bg-green-100 text-green-800';
        case 'running':
            return 'bg-blue-100 text-blue-800 animate-pulse';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status) => {
    switch (status) {
        case 'finished':
            return 'Zakończony';
        case 'running':
            return 'W trakcie';
        case 'cancelled':
            return 'Anulowany';
        default:
            return 'Nieznany';
    }
};

const getProgressBarClass = (status) => {
    switch (status) {
        case 'finished':
            return 'bg-green-500';
        case 'running':
            return 'bg-blue-500';
        case 'cancelled':
            return 'bg-red-500';
        default:
            return 'bg-gray-400';
    }
};

const formatTimestamp = (timestamp) => {
    if (!timestamp) return '—';
    return new Date(timestamp * 1000).toLocaleString('pl-PL');
};

const applyFilters = () => {
    updateUrl();
};

const debouncedSearch = debounce(() => {
    applyFilters();
}, 500);

const clearFilters = () => {
    filters.value = {
        search: '',
        status: '',
        date_from: null,
        date_to: null,
        progress_min: null,
        progress_max: null,
        sort_by: 'created_at',
        sort_direction: 'desc',
    };
    updateUrl();
};

const changePerPage = () => {
    updateUrl();
};

const onPageChange = (event) => {
    currentPage.value = event.first;
    updateUrl();
};

const updateUrl = () => {
    const query = {
        page: Math.floor(currentPage.value / perPage.value) + 1,
        per_page: perPage.value,
    };

    // Add filters
    if (filters.value.search) query.search = filters.value.search;
    if (filters.value.status) query.status = filters.value.status;
    if (filters.value.date_from) query.date_from = filters.value.date_from.toISOString().split('T')[0];
    if (filters.value.date_to) query.date_to = filters.value.date_to.toISOString().split('T')[0];
    if (filters.value.progress_min !== null) query.progress_min = filters.value.progress_min;
    if (filters.value.progress_max !== null) query.progress_max = filters.value.progress_max;
    if (filters.value.sort_by) query.sort_by = filters.value.sort_by;
    if (filters.value.sort_direction) query.sort_direction = filters.value.sort_direction;

    router.get(route('batches.index'), query, {
        preserveState: true,
        replace: true,
    });
};
</script>

<style scoped>
.card {
    @apply bg-white rounded-lg shadow-md p-6;
}

:deep(.p-paginator) {
    @apply justify-center;
}

:deep(.p-dropdown) {
    @apply w-full;
}

:deep(.p-calendar) {
    @apply w-full;
}

:deep(.p-inputnumber) {
    @apply w-full;
}
</style>
