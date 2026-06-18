<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import {BaseItemDuplicateViewResource} from "@/Resources/BaseItemDuplicateView.resource";
import {Link, router} from '@inertiajs/vue3';
import {route} from "ziggy-js";
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import {computed, ref} from "vue";

type DropdownOption = {
    label: string
    value: string | null
}

type DuplicateFilters = {
    search: string | null
    category: string | null
    rarity: string | null
}

type PaginationMeta = {
    current_page: number
    from: number | null
    last_page: number
    per_page: number
    to: number | null
    total: number
}

type PaginatedDuplicates = {
    data: BaseItemDuplicateViewResource[]
    meta: PaginationMeta
}

const props = withDefaults(defineProps<{
    duplicates: PaginatedDuplicates
    filters: DuplicateFilters
    perPage: number
    categoryOptions: DropdownOption[]
    rarityOptions: DropdownOption[]
}>(), {
    categoryOptions: () => [],
    rarityOptions: () => [],
});

const search = ref(props.filters.search ?? '');
const selectedCategory = ref<string | null>(props.filters.category ?? null);
const selectedRarity = ref<string | null>(props.filters.rarity ?? null);
const rows = ref(props.perPage);
const first = ref(((props.duplicates.meta.current_page ?? 1) - 1) * (props.duplicates.meta.per_page ?? props.perPage));

const categoryFilterOptions = computed<DropdownOption[]>(() => [
    {label: 'Wszystkie kategorie', value: null},
    ...props.categoryOptions,
]);

const rarityFilterOptions = computed<DropdownOption[]>(() => [
    {label: 'Wszystkie rzadkości', value: null},
    ...props.rarityOptions,
]);

const hasFilters = computed(() => (
    search.value.trim() !== ''
    || selectedCategory.value !== null
    || selectedRarity.value !== null
));

const applyFilters = () => {
    first.value = 0;
    updateUrl(1);
};

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = null;
    selectedRarity.value = null;
    first.value = 0;
    updateUrl(1);
};

const onPageChange = (event: { first: number, rows: number, page: number }) => {
    first.value = event.first;
    rows.value = event.rows;
    updateUrl(event.page + 1);
};

const updateUrl = (page: number) => {
    const query: Record<string, string | number> = {
        page,
        per_page: rows.value,
    };

    const trimmedSearch = search.value.trim();

    if (trimmedSearch !== '') {
        query.search = trimmedSearch;
    }

    if (selectedCategory.value !== null) {
        query.category = selectedCategory.value;
    }

    if (selectedRarity.value !== null) {
        query.rarity = selectedRarity.value;
    }

    router.get(route('base-items.duplicates.index'), query, {
        preserveState: true,
        replace: true,
    });
};

const formatRefreshedAt = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('pl-PL');
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="flex flex-col gap-1">
                    <h1 class="m-0 text-3xl font-bold">Potencjalne duplikaty bazowych przedmiotów</h1>
                    <div class="text-sm text-surface-500 dark:text-surface-400">
                        Podejrzany nieużywany przedmiot zestawiony z używanym odpowiednikiem.
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex min-w-56 flex-col gap-2">
                        <label for="duplicate-search" class="text-sm font-semibold">Szukaj</label>
                        <InputText
                            id="duplicate-search"
                            v-model="search"
                            placeholder="Nazwa lub ID"
                            @keydown.enter="applyFilters"
                        />
                    </div>

                    <div class="flex min-w-56 flex-col gap-2">
                        <label for="duplicate-category" class="text-sm font-semibold">Kategoria</label>
                        <Dropdown
                            id="duplicate-category"
                            v-model="selectedCategory"
                            :options="categoryFilterOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                        />
                    </div>

                    <div class="flex min-w-52 flex-col gap-2">
                        <label for="duplicate-rarity" class="text-sm font-semibold">Rzadkość</label>
                        <Dropdown
                            id="duplicate-rarity"
                            v-model="selectedRarity"
                            :options="rarityFilterOptions"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                        />
                    </div>

                    <Button
                        label="Zastosuj"
                        icon="pi pi-search"
                        @click="applyFilters"
                    />
                    <Button
                        label="Wyczyść"
                        icon="pi pi-times"
                        severity="secondary"
                        outlined
                        :disabled="!hasFilters"
                        @click="clearFilters"
                    />
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm text-surface-600 dark:text-surface-300">
                <div>
                    Wyświetlanie {{ duplicates.meta.from ?? 0 }}-{{ duplicates.meta.to ?? 0 }}
                    z {{ duplicates.meta.total }} podejrzeń
                </div>

                <Link :href="route('base-items.index')" class="text-primary no-underline hover:underline">
                    Lista wszystkich przedmiotów
                </Link>
            </div>

            <div v-if="duplicates.data.length === 0" class="rounded border border-surface-200 p-8 text-center text-surface-500 dark:border-surface-700 dark:text-surface-400">
                Brak potencjalnych duplikatów dla obecnych filtrów.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-surface-200 text-sm text-surface-500 dark:border-surface-700 dark:text-surface-400">
                            <th class="w-[32%] px-3 py-3 font-semibold">Podejrzany do usunięcia</th>
                            <th class="w-[32%] px-3 py-3 font-semibold">Przedmiot w użyciu</th>
                            <th class="w-[24%] px-3 py-3 font-semibold">Wspólne cechy</th>
                            <th class="w-[12%] px-3 py-3 font-semibold">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="duplicate in duplicates.data"
                            :key="duplicate.id"
                            class="border-b border-surface-100 align-top hover:bg-surface-50 dark:border-surface-800 dark:hover:bg-surface-900/40"
                        >
                            <td class="px-3 py-4">
                                <div class="flex items-start gap-3">
                                    <img
                                        :src="duplicate.duplicate_item.src"
                                        :alt="duplicate.duplicate_item.name"
                                        class="h-12 w-12 rounded border border-surface-200 object-contain p-1 dark:border-surface-700"
                                    />
                                    <div class="flex min-w-0 flex-col gap-2">
                                        <Link
                                            :href="route('base-items.show', duplicate.duplicate_item.id)"
                                            class="font-semibold text-primary no-underline hover:underline"
                                        >
                                            #{{ duplicate.duplicate_item.id }} {{ duplicate.duplicate_item.name }}
                                        </Link>
                                        <div class="flex flex-wrap gap-2">
                                            <Tag value="Nie używany" severity="info" />
                                            <Tag
                                                :value="`${duplicate.duplicate_item.usage_source_count} źródeł`"
                                                severity="secondary"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-4">
                                <div class="flex items-start gap-3">
                                    <img
                                        :src="duplicate.used_item.src"
                                        :alt="duplicate.used_item.name"
                                        class="h-12 w-12 rounded border border-surface-200 object-contain p-1 dark:border-surface-700"
                                    />
                                    <div class="flex min-w-0 flex-col gap-2">
                                        <Link
                                            :href="route('base-items.show', duplicate.used_item.id)"
                                            class="font-semibold text-primary no-underline hover:underline"
                                        >
                                            #{{ duplicate.used_item.id }} {{ duplicate.used_item.name }}
                                        </Link>
                                        <div class="flex flex-wrap gap-2">
                                            <Tag value="W użyciu" severity="success" />
                                            <Tag
                                                :value="`${duplicate.used_item.usage_source_count} źródeł`"
                                                severity="secondary"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-4">
                                <div class="flex flex-col gap-2 text-sm">
                                    <div>
                                        <span class="font-semibold">Nazwa:</span>
                                        {{ duplicate.name }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Kategoria:</span>
                                        {{ duplicate.category_name ?? duplicate.category ?? '-' }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Rzadkość:</span>
                                        {{ duplicate.rarity_name ?? duplicate.rarity ?? '-' }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Wymagany poziom:</span>
                                        {{ duplicate.need_level ?? '-' }}
                                    </div>
                                    <div class="text-xs text-surface-500 dark:text-surface-400">
                                        Odświeżono: {{ formatRefreshedAt(duplicate.refreshed_at) }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-4">
                                <div class="flex flex-col gap-2">
                                    <Link :href="route('base-items.show', duplicate.duplicate_item.id)">
                                        <Button
                                            label="Podejrzany"
                                            icon="pi pi-eye"
                                            size="small"
                                            class="w-full"
                                        />
                                    </Link>
                                    <Link :href="route('base-items.show', duplicate.used_item.id)">
                                        <Button
                                            label="Używany"
                                            icon="pi pi-check-circle"
                                            size="small"
                                            severity="secondary"
                                            outlined
                                            class="w-full"
                                        />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-center">
                <Paginator
                    :first="first"
                    :rows="rows"
                    :total-records="duplicates.meta.total"
                    :rows-per-page-options="[25, 50, 100]"
                    @page="onPageChange"
                />
            </div>
        </div>
    </AppLayout>
</template>
