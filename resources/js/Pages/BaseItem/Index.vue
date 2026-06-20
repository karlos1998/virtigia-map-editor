<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import type {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import { Link, router, useForm } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {computed, onMounted, ref} from "vue";
import AutoComplete from 'primevue/autocomplete';
import Dropdown from 'primevue/dropdown';
import Fieldset from 'primevue/fieldset';
import Checkbox from 'primevue/checkbox';
import ProgressSpinner from 'primevue/progressspinner';
import Menu from 'primevue/menu';
import {useToast} from "primevue";
import axios from 'axios';
import {
    additionalAttributes,
    booleanAttributes,
    type BaseItemAttributeOption,
} from './AttributeOptions';

type Data = {
    data: BaseItemResource
}

type BaseItemFilters = {
    description: string | null
    legendary_bonus: string | null
    attribute_keys: string[]
}

type LegendaryBonusOption = {
    label: string
    value: string
    bonus_value: number
}

type AttributePoint = {
    name: string
    description: string
}

type AttributeFilterGroup = {
    label: string
    options: BaseItemAttributeOption[]
}

const props = withDefaults(defineProps<{
    filters?: BaseItemFilters
    legendaryBonusOptions?: LegendaryBonusOption[]
}>(), {
    filters: () => ({
        description: null,
        legendary_bonus: null,
        attribute_keys: [],
    }),
    legendaryBonusOptions: () => [],
})

const description = ref(props.filters.description ?? '');
const selectedLegendaryBonus = ref<string | null>(props.filters.legendary_bonus ?? null);
const selectedAttributeKeys = ref<string[]>([...(props.filters.attribute_keys ?? [])]);
const isLoadingAttributePointOptions = ref(false);
const attributePointOptions = ref<BaseItemAttributeOption[]>([]);
const manualAttributePointOptions = ref<BaseItemAttributeOption[]>([]);
const isAdvancedFiltersCollapsed = ref(!props.filters.description && !props.filters.legendary_bonus && selectedAttributeKeys.value.length === 0);
const selectedBaseItems = ref<BaseItemResource[]>([]);
const bulkActionsMenu = ref();
const isBulkDescriptionDialogVisible = ref(false);
const isBulkLootDialogVisible = ref(false);
const selectedBaseNpc = ref<BaseNpcResource | null>(null);
const filteredBaseNpcs = ref<BaseNpcResource[]>([]);
const toast = useToast();

const bulkDescriptionForm = useForm({
    item_ids: [] as number[],
    search_phrase: '',
    replacement_phrase: '',
});

const bulkLootForm = useForm({
    item_ids: [] as number[],
    base_npc_id: null as number | null,
});

const legendaryBonusFilterOptions = computed(() => [
    {label: 'Dowolny bonus', value: null, bonus_value: 0},
    ...props.legendaryBonusOptions,
]);

const attributeFilterGroups = computed<AttributeFilterGroup[]>(() => [
    {
        label: 'Punkty atrybutów',
        options: attributePointOptions.value,
    },
    {
        label: 'Manualne punkty atrybutów',
        options: manualAttributePointOptions.value,
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

const hasActiveAdvancedFilters = computed(() => (
    description.value.trim() !== ''
    || selectedLegendaryBonus.value !== null
    || selectedAttributeKeys.value.length > 0
));

const selectedBaseItemIds = computed(() => selectedBaseItems.value.map((baseItem) => baseItem.id));

const canSubmitBulkDescriptionCorrection = computed(() => (
    selectedBaseItemIds.value.length > 0
    && bulkDescriptionForm.search_phrase.length > 0
    && !bulkDescriptionForm.processing
));

const canSubmitBulkLootAssignment = computed(() => (
    selectedBaseItemIds.value.length > 0
    && selectedBaseNpc.value !== null
    && !bulkLootForm.processing
));

const bulkActionItems = computed(() => [
    {
        label: 'Poprawa opisu',
        icon: 'pi pi-align-left',
        command: () => openBulkDescriptionDialog(),
    },
    {
        label: 'Przypisz loot do Base NPC',
        icon: 'pi pi-sitemap',
        command: () => openBulkLootDialog(),
    },
]);

const fetchAttributePointOptions = async () => {
    try {
        isLoadingAttributePointOptions.value = true;
        const response = await axios.get('/api/base-items/attribute-points');
        const attributePoints = response.data?.attributePoints ?? [];
        const manualAttributePoints = response.data?.manualAttributePoints ?? [];

        attributePointOptions.value = attributePoints.map((attribute: AttributePoint) => ({
            key: attribute.name,
            label: attribute.description,
        }));
        manualAttributePointOptions.value = manualAttributePoints.map((attribute: AttributePoint) => ({
            key: attribute.name,
            label: attribute.description,
        }));
    } catch {
        attributePointOptions.value = [];
        manualAttributePointOptions.value = [];
    } finally {
        isLoadingAttributePointOptions.value = false;
    }
}

const reloadWithAdvancedFilters = () => {
    const descriptionValue = description.value.trim();
    const filters: Record<string, string | string[]> = {};

    if (descriptionValue !== '') {
        filters.description = descriptionValue;
    }

    if (selectedLegendaryBonus.value !== null) {
        filters.legendary_bonus = selectedLegendaryBonus.value;
    }

    if (selectedAttributeKeys.value.length > 0) {
        filters.attribute_keys = selectedAttributeKeys.value;
    }

    router.get(route('base-items.index'), filters, {
        only: ['items', 'filters'],
        preserveState: true,
        replace: true,
    });
}

const clearAdvancedFilters = () => {
    description.value = '';
    selectedLegendaryBonus.value = null;
    selectedAttributeKeys.value = [];
    reloadWithAdvancedFilters();
}

const toggleBulkActionsMenu = (event: Event) => {
    bulkActionsMenu.value?.toggle(event);
}

const openBulkDescriptionDialog = () => {
    bulkDescriptionForm.clearErrors();
    bulkDescriptionForm.item_ids = selectedBaseItemIds.value;
    bulkDescriptionForm.search_phrase = '';
    bulkDescriptionForm.replacement_phrase = '';
    isBulkDescriptionDialogVisible.value = true;
}

const openBulkLootDialog = () => {
    bulkLootForm.clearErrors();
    bulkLootForm.item_ids = selectedBaseItemIds.value;
    bulkLootForm.base_npc_id = null;
    selectedBaseNpc.value = null;
    filteredBaseNpcs.value = [];
    isBulkLootDialogVisible.value = true;
}

const searchBaseNpcs = async ({ query }: { query: string }) => {
    const { data } = await axios.get(route('base-npcs.search', { query }));
    filteredBaseNpcs.value = Array.isArray(data) ? data : (data.data ?? []);
}

const submitBulkDescriptionCorrection = () => {
    bulkDescriptionForm.item_ids = selectedBaseItemIds.value;

    bulkDescriptionForm.patch(route('base-items.bulk.description.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Udało się',
                detail: 'Opisy wybranych przedmiotów zostały poprawione.',
                life: 3000,
            });
            isBulkDescriptionDialogVisible.value = false;
            selectedBaseItems.value = [];
            bulkDescriptionForm.reset();
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: Object.values(errors)[0] ?? 'Nie udało się poprawić opisów.',
                life: 5000,
            });
        },
    });
}

const submitBulkLootAssignment = () => {
    if (!selectedBaseNpc.value) {
        return;
    }

    bulkLootForm.item_ids = selectedBaseItemIds.value;
    bulkLootForm.base_npc_id = selectedBaseNpc.value.id;

    bulkLootForm.post(route('base-items.bulk.base-npc-loots.attach'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Udało się',
                detail: 'Wybrane przedmioty zostały przypisane jako loot.',
                life: 3000,
            });
            isBulkLootDialogVisible.value = false;
            selectedBaseItems.value = [];
            selectedBaseNpc.value = null;
            bulkLootForm.reset();
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: Object.values(errors)[0] ?? 'Nie udało się przypisać lootów.',
                life: 5000,
            });
        },
    });
}

onMounted(() => {
    fetchAttributePointOptions();
});
</script>

<template>
    <AppLayout>
        <div class="card">
            <Fieldset
                legend="Filtry zaawansowane"
                :toggleable="true"
                v-model:collapsed="isAdvancedFiltersCollapsed"
                class="mb-4"
            >
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-filter text-primary" />
                        <h5 class="m-0">Filtry atrybutów</h5>
                        <Tag v-if="hasActiveAdvancedFilters" value="aktywne" severity="success" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="flex flex-col gap-2">
                            <label for="description-filter" class="font-semibold">Opis</label>
                            <InputText
                                id="description-filter"
                                v-model="description"
                                placeholder="Wpisz fragment opisu"
                                @keydown.enter="reloadWithAdvancedFilters"
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
                            @click="reloadWithAdvancedFilters"
                        />
                        <Button
                            label="Wyczyść"
                            icon="pi pi-times"
                            severity="secondary"
                            outlined
                            :disabled="!hasActiveAdvancedFilters"
                            @click="clearAdvancedFilters"
                        />
                    </div>
                </div>
            </Fieldset>

            <div
                v-if="selectedBaseItems.length > 0"
                class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded border border-surface-200 p-3 dark:border-surface-700"
            >
                <div class="flex items-center gap-2 text-sm font-medium">
                    <i class="pi pi-check-square text-primary" />
                    <span>Wybrano {{ selectedBaseItems.length }} przedmiotów</span>
                </div>

                <div>
                    <Button
                        label="Operacje masowe"
                        icon="pi pi-bars"
                        severity="secondary"
                        outlined
                        @click="toggleBulkActionsMenu"
                    />
                    <Menu
                        ref="bulkActionsMenu"
                        :model="bulkActionItems"
                        popup
                    />
                </div>
            </div>

            <AdvanceTable
                v-model:selection="selectedBaseItems"
                prop-name="items"
            >
                <Column selection-mode="multiple" style="width: 3rem" />
                <AdvanceColumn field="id" header="ID" style="width: 5%" />

                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista Bazowych Przedmiotów</h4>
                        <div class="flex items-center gap-2">
                            <Link :href="route('base-items.duplicates.index')">
                                <Button
                                    icon="pi pi-clone"
                                    label="Duplikaty"
                                    severity="secondary"
                                    outlined
                                />
                            </Link>
                            <Link :href="route('base-items.create')">
                                <Button
                                    icon="pi pi-plus"
                                    label="Nowy przedmiot"
                                    severity="success"
                                />
                            </Link>
                            <IconField>
                                <InputIcon>
                                    <i class="pi pi-search"/>
                                </InputIcon>
                                <InputText
                                    :value="globalFilterValue"
                                    @update:model-value="globalFilterUpdated"
                                    placeholder="Szukaj"
                                />
                            </IconField>
                        </div>
                    </div>
                </template>

                <AdvanceColumn header="Podgląd">
                    <template #body="{ data }: Data">
                        <!-- todo ! tip nie dziala ;c -->
                        <img
                            class="h-12 w-12 object-cover"
                            :src="data.src"
                            v-tip.item.top.show-id="data"
                            alt=""
                        />
                    </template>
                </AdvanceColumn>

<!--                <AdvanceColumn field="src" header="Grafika">-->
<!--                    <template #body="{ data }: Data">-->
<!--                        <img alt="" :src="`https://s3.letscode.it/virtigia-assets/img/${data.src}`" />-->
<!--                    </template>-->
<!--                </AdvanceColumn>-->

                <AdvanceColumn field="name" header="Name" />

                <AdvanceColumn field="category_name" header="Kategoria">
                    <template #body="{ data }: Data">
                        {{data.category_name }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="currency_name" header="Waluta">
                    <template #body="{ data }: Data">
                        {{data.currency_name }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="need_professions" header="Wymagana profesja">
                    <template #body="{ data }: Data">
                        {{data.need_professions.join(', ') || 'Dowolna' }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="rarity" header="Rzadkość">
                    <template #body="{ data }: Data">
                        {{ data.rarity }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="need_level" header="Wymagany poziom">
                    <template #body="{ data }: Data">
                        {{data.need_level || '-' }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="in_use" header="W użyciu">
                    <template #body="{ data }: Data">
                        <Tag
                            v-if="data.in_use"
                            value="W użyciu"
                            severity="success"
                        />
                        <Tag
                            v-else
                            value="Nie używany"
                            severity="info"
                        />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn header="Gdzie zdobyć" style="min-width: 28rem;">
                    <template #body="{ data }: Data">
                        <div v-if="data.usage_sources.length" class="flex flex-col gap-2">
                            <div
                                v-for="source in data.usage_sources.slice(0, 3)"
                                :key="`${data.id}-${source.type}-${source.shop?.id ?? 'no-shop'}-${source.npc?.id ?? 'no-npc'}-${source.location?.map_id ?? 'no-map'}-${source.location?.x ?? 'x'}-${source.location?.y ?? 'y'}`"
                                class="flex items-center gap-3 rounded border border-surface-200 px-3 py-2 dark:border-surface-700"
                            >
                                <Link
                                    v-if="source.npc?.src"
                                    :href="route('base-npcs.show', source.npc.id)"
                                >
                                    <img
                                        :src="source.npc.src"
                                        :alt="source.npc.name"
                                        class="h-10 w-10 rounded object-cover"
                                    />
                                </Link>

                                <div class="flex flex-col gap-1 text-sm">
                                    <div class="font-medium">
                                        <span v-if="source.location">
                                            {{ source.location.label }}
                                        </span>
                                        <Link
                                            v-else-if="source.shop"
                                            :href="route('shops.show', source.shop.id)"
                                            class="font-medium text-primary no-underline hover:underline"
                                        >
                                            Shop #{{ source.shop.id }} bez przypisanej lokalizacji NPC
                                        </Link>
                                        <span v-else>
                                            Brak lokalizacji
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1 text-surface-500 dark:text-surface-400">
                                        <Link
                                            v-if="source.shop"
                                            :href="route('shops.show', source.shop.id)"
                                            class="no-underline hover:underline"
                                        >
                                            {{ source.shop.name }} (#{{ source.shop.id }})
                                        </Link>

                                        <span v-if="source.shop && source.npc">•</span>

                                        <Link
                                            v-if="source.npc"
                                            :href="route('base-npcs.show', source.npc.id)"
                                            class="no-underline hover:underline"
                                        >
                                            {{ source.type === 'loot' ? 'Loot: ' : '' }}{{ source.npc.name }}
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="data.usage_source_count > 3"
                                class="text-xs text-surface-500 dark:text-surface-400"
                            >
                                +{{ data.usage_source_count - 3 }} kolejnych lokalizacji
                            </div>
                        </div>

                        <span v-else class="text-sm text-surface-500 dark:text-surface-400">
                            -
                        </span>
                    </template>
                </AdvanceColumn>

                <Column header="Action" >
                    <template #body="slotProps">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Link :href="route('base-items.show', slotProps.data.id)">
                                    <Button
                                        class="px-2"
                                        icon="pi pi-eye"
                                        label="Podgląd"
                                    />
                                </Link>
                            </span>
                        </div>
                    </template>
                </Column>


                <AdvanceColumn field="src" header="Link do grafiki">
                    <template #body="{ data }: Data">
                        {{data.src}}
                    </template>
                </AdvanceColumn>

            </AdvanceTable>

            <Dialog
                v-model:visible="isBulkDescriptionDialogVisible"
                modal
                header="Poprawa opisu"
                :style="{ width: '34rem' }"
            >
                <form class="flex flex-col gap-4" @submit.prevent="submitBulkDescriptionCorrection">
                    <Message severity="info" :closable="false">
                        Wybrano {{ selectedBaseItems.length }} przedmiotów.
                    </Message>

                    <div class="flex flex-col gap-2">
                        <label for="bulk-description-search" class="font-semibold">Fraza do poprawy</label>
                        <InputText
                            id="bulk-description-search"
                            v-model="bulkDescriptionForm.search_phrase"
                            :class="{ 'p-invalid': bulkDescriptionForm.errors.search_phrase }"
                            autofocus
                            placeholder="Wigilia 2025 r."
                        />
                        <small v-if="bulkDescriptionForm.errors.search_phrase" class="text-red-500">
                            {{ bulkDescriptionForm.errors.search_phrase }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="bulk-description-replacement" class="font-semibold">Fraza docelowa</label>
                        <InputText
                            id="bulk-description-replacement"
                            v-model="bulkDescriptionForm.replacement_phrase"
                            :class="{ 'p-invalid': bulkDescriptionForm.errors.replacement_phrase }"
                            placeholder="Wigilia #YEAR r."
                        />
                        <small v-if="bulkDescriptionForm.errors.replacement_phrase" class="text-red-500">
                            {{ bulkDescriptionForm.errors.replacement_phrase }}
                        </small>
                    </div>
                </form>

                <template #footer>
                    <Button
                        label="Anuluj"
                        severity="secondary"
                        outlined
                        :disabled="bulkDescriptionForm.processing"
                        @click="isBulkDescriptionDialogVisible = false"
                    />
                    <Button
                        label="Zapisz"
                        icon="pi pi-check"
                        :disabled="!canSubmitBulkDescriptionCorrection"
                        :loading="bulkDescriptionForm.processing"
                        @click="submitBulkDescriptionCorrection"
                    />
                </template>
            </Dialog>

            <Dialog
                v-model:visible="isBulkLootDialogVisible"
                modal
                header="Przypisz loot do Base NPC"
                :style="{ width: '36rem' }"
            >
                <form class="flex flex-col gap-4" @submit.prevent="submitBulkLootAssignment">
                    <Message severity="info" :closable="false">
                        Wybrano {{ selectedBaseItems.length }} przedmiotów.
                    </Message>

                    <div class="flex flex-col gap-2">
                        <label for="bulk-loot-base-npc" class="font-semibold">Base NPC</label>
                        <AutoComplete
                            input-id="bulk-loot-base-npc"
                            v-model="selectedBaseNpc"
                            :suggestions="filteredBaseNpcs"
                            @complete="searchBaseNpcs"
                            :option-label="(baseNpc) => baseNpc?.name || ''"
                            placeholder="Wyszukaj Base NPC"
                            class="w-full"
                            :class="{ 'p-invalid': bulkLootForm.errors.base_npc_id }"
                            force-selection
                            dropdown
                            fluid
                        >
                            <template #option="slotProps">
                                <div class="flex items-center gap-3">
                                    <img
                                        class="h-12 w-12 object-cover"
                                        :src="slotProps.option.src"
                                        :alt="slotProps.option.name"
                                        v-tip.npc.top.show-id="slotProps.option"
                                    />
                                    <span class="font-semibold text-gray-800 dark:text-surface-100">
                                        [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                                    </span>
                                </div>
                            </template>
                        </AutoComplete>
                        <small v-if="bulkLootForm.errors.base_npc_id" class="text-red-500">
                            {{ bulkLootForm.errors.base_npc_id }}
                        </small>
                        <small v-if="bulkLootForm.errors.item_ids" class="text-red-500">
                            {{ bulkLootForm.errors.item_ids }}
                        </small>
                    </div>
                </form>

                <template #footer>
                    <Button
                        label="Anuluj"
                        severity="secondary"
                        outlined
                        :disabled="bulkLootForm.processing"
                        @click="isBulkLootDialogVisible = false"
                    />
                    <Button
                        label="Przypisz"
                        icon="pi pi-check"
                        :disabled="!canSubmitBulkLootAssignment"
                        :loading="bulkLootForm.processing"
                        @click="submitBulkLootAssignment"
                    />
                </template>
            </Dialog>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
