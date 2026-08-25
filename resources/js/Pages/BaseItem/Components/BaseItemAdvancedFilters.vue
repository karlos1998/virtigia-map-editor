<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AutoComplete from 'primevue/autocomplete';
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import Fieldset from 'primevue/fieldset';
import ProgressSpinner from 'primevue/progressspinner';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import {
    additionalAttributes,
    booleanAttributes,
    type BaseItemAttributeOption,
} from '../AttributeOptions';

export type BaseItemFilters = {
    description: string | null;
    legendary_bonus: string | null;
    attribute_keys: string[];
    shop_ids: number[];
};

export type ShopOption = {
    id: number;
    name: string;
};

export type LegendaryBonusOption = {
    label: string;
    value: string;
    bonus_value: number;
};

type AttributeFilterGroup = {
    label: string;
    options: BaseItemAttributeOption[];
};

const props = defineProps<{
    filters: BaseItemFilters;
    selectedShopOptions: ShopOption[];
    legendaryBonusOptions: LegendaryBonusOption[];
    attributePointOptions: BaseItemAttributeOption[];
    manualAttributePointOptions: BaseItemAttributeOption[];
    isLoadingAttributePointOptions: boolean;
}>();

const description = ref(props.filters.description ?? '');
const selectedLegendaryBonus = ref<string | null>(props.filters.legendary_bonus ?? null);
const selectedAttributeKeys = ref<string[]>([...(props.filters.attribute_keys ?? [])]);
const selectedShops = ref<ShopOption[]>([...props.selectedShopOptions]);
const filteredShops = ref<ShopOption[]>([]);
let shopSearchRequestId = 0;

const isCollapsed = ref(
    !props.filters.description
    && !props.filters.legendary_bonus
    && selectedAttributeKeys.value.length === 0
    && selectedShops.value.length === 0,
);

const legendaryBonusFilterOptions = computed(() => [
    { label: 'Dowolny bonus', value: null, bonus_value: 0 },
    ...props.legendaryBonusOptions,
]);

const attributeFilterGroups = computed<AttributeFilterGroup[]>(() => [
    {
        label: 'Punkty atrybutów',
        options: props.attributePointOptions,
    },
    {
        label: 'Manualne punkty atrybutów',
        options: props.manualAttributePointOptions,
    },
    {
        label: 'Atrybuty logiczne',
        options: booleanAttributes,
    },
    {
        label: 'Dodatkowe atrybuty',
        options: additionalAttributes,
    },
].filter(group => group.options.length > 0));

const hasActiveFilters = computed(() => (
    description.value.trim() !== ''
    || selectedLegendaryBonus.value !== null
    || selectedAttributeKeys.value.length > 0
    || selectedShops.value.length > 0
));

const applyFilters = () => {
    const descriptionValue = description.value.trim();
    const filters: Record<string, string | string[] | number[]> = {};

    if (descriptionValue !== '') {
        filters.description = descriptionValue;
    }

    if (selectedLegendaryBonus.value !== null) {
        filters.legendary_bonus = selectedLegendaryBonus.value;
    }

    if (selectedAttributeKeys.value.length > 0) {
        filters.attribute_keys = selectedAttributeKeys.value;
    }

    if (selectedShops.value.length > 0) {
        filters.shop_ids = selectedShops.value.map(shop => shop.id);
    }

    router.get(route('base-items.index'), filters, {
        only: ['items', 'filters'],
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    description.value = '';
    selectedLegendaryBonus.value = null;
    selectedAttributeKeys.value = [];
    selectedShops.value = [];
    filteredShops.value = [];
    shopSearchRequestId++;
    applyFilters();
};

const searchShops = async ({ query }: { query: string }) => {
    const normalizedQuery = query.trim();
    const requestId = ++shopSearchRequestId;

    if (normalizedQuery === '') {
        filteredShops.value = [];

        return;
    }

    try {
        const { data } = await axios.get(route('shops.search'), {
            params: { query: normalizedQuery },
        });

        if (requestId !== shopSearchRequestId) {
            return;
        }

        const selectedShopIds = new Set(selectedShops.value.map(shop => shop.id));
        const shops: ShopOption[] = Array.isArray(data) ? data : (data.data ?? []);

        filteredShops.value = shops.filter(shop => !selectedShopIds.has(shop.id));
    } catch {
        if (requestId === shopSearchRequestId) {
            filteredShops.value = [];
        }
    }
};
</script>

<template>
    <Fieldset
        v-model:collapsed="isCollapsed"
        legend="Filtry zaawansowane"
        :toggleable="true"
        class="mb-4"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <i class="pi pi-filter text-primary" />
                <h5 class="m-0">Filtry atrybutów</h5>
                <Tag v-if="hasActiveFilters" value="aktywne" severity="success" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex flex-col gap-2">
                    <label for="description-filter" class="font-semibold">Opis</label>
                    <InputText
                        id="description-filter"
                        v-model="description"
                        placeholder="Wpisz fragment opisu"
                        @keydown.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="legendary-bonus-filter" class="font-semibold">Bonus legendarny</label>
                    <Dropdown
                        id="legendary-bonus-filter"
                        v-model="selectedLegendaryBonus"
                        :options="legendaryBonusFilterOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Wybierz bonus"
                        show-clear
                        class="w-full"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="shop-filter" class="font-semibold">Należy do sklepu</label>
                    <AutoComplete
                        v-model="selectedShops"
                        input-id="shop-filter"
                        :suggestions="filteredShops"
                        option-label="name"
                        placeholder="Wpisz nazwę sklepu"
                        multiple
                        force-selection
                        :delay="300"
                        class="w-full"
                        input-class="w-full"
                        @complete="searchShops"
                    >
                        <template #option="{ option }">
                            {{ option.name }} (#{{ option.id }})
                        </template>
                    </AutoComplete>
                    <small class="text-surface-500 dark:text-surface-400">
                        Przedmiot może należeć do dowolnego z wybranych sklepów.
                    </small>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2">
                    <h6 class="m-0 font-semibold">Atrybuty na przedmiocie</h6>
                    <ProgressSpinner
                        v-if="isLoadingAttributePointOptions"
                        style="width: 1rem; height: 1rem"
                    />
                </div>

                <div class="flex flex-col gap-4">
                    <div
                        v-for="group in attributeFilterGroups"
                        :key="group.label"
                        class="flex flex-col gap-2"
                    >
                        <div class="text-sm font-semibold text-surface-600 dark:text-surface-300">
                            {{ group.label }}
                        </div>

                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                            <label
                                v-for="option in group.options"
                                :key="`${group.label}-${option.key}`"
                                :for="`attribute-filter-${option.key}`"
                                class="flex min-h-12 cursor-pointer items-center gap-3 rounded border border-surface-200 px-3 py-2 text-sm transition-colors hover:border-primary dark:border-surface-700"
                            >
                                <Checkbox
                                    v-model="selectedAttributeKeys"
                                    :input-id="`attribute-filter-${option.key}`"
                                    :value="option.key"
                                />
                                <span class="flex min-w-0 flex-col gap-1">
                                    <span class="font-medium leading-tight">{{ option.label }}</span>
                                    <span class="truncate text-xs text-surface-500 dark:text-surface-400">{{ option.key }}</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    label="Zastosuj"
                    icon="pi pi-search"
                    severity="success"
                    @click="applyFilters"
                />
                <Button
                    label="Wyczyść"
                    icon="pi pi-times"
                    severity="secondary"
                    outlined
                    :disabled="!hasActiveFilters"
                    @click="clearFilters"
                />
            </div>
        </div>
    </Fieldset>
</template>
