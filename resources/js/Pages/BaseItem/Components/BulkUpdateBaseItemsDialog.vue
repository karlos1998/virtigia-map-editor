<script setup lang="ts">
import type { BaseItemResource } from '@/Resources/BaseItem.resource';
import { useForm } from '@inertiajs/vue3';
import Calendar from 'primevue/calendar';
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import MultiSelect from 'primevue/multiselect';
import { useToast } from 'primevue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { calculateBaseItemPrice } from '../BaseItemPriceCalculator';

export type BulkBaseItemOperation =
    | 'binding'
    | 'boolean_attribute'
    | 'category'
    | 'clear_attributes'
    | 'currency'
    | 'legendary_bonus'
    | 'lifespan'
    | 'name'
    | 'price'
    | 'price_adjustment'
    | 'rarity'
    | 'required_level'
    | 'specific_currency_price';

type DropdownOption = {
    label: string;
    value: string;
};

type AttributeOptionGroup = {
    label: string;
    options: DropdownOption[];
};

type RecalculatedPrice = {
    item_id: number;
    price: number;
};

const props = defineProps<{
    items: BaseItemResource[];
    operation: BulkBaseItemOperation | null;
    rarityOptions: DropdownOption[];
    currencyOptions: DropdownOption[];
    categoryOptions: DropdownOption[];
    legendaryBonusOptions: DropdownOption[];
    booleanAttributeOptions: DropdownOption[];
    clearAttributeGroups: AttributeOptionGroup[];
}>();

const emit = defineEmits<{
    completed: [];
}>();

const visible = defineModel<boolean>('visible', { default: false });
const selectedValue = ref<string | null>(null);
const numericValue = ref<number | null>(null);
const recalculatePrices = ref(true);
const enabled = ref(true);
const clearValue = ref(false);
const lifespanAttributeKey = ref<'expiresOn' | 'timeToDisappear'>('expiresOn');
const expirationDate = ref<Date | null>(null);
const selectedAttributePaths = ref<string[]>([]);
const nameMode = ref<'replace' | 'prefix' | 'suffix'>('replace');
const searchPhrase = ref('');
const replacementPhrase = ref('');
const priceAdjustmentMode = ref<'fixed' | 'percentage' | 'multiplier'>('fixed');
const priceAdjustmentValue = ref<number | null>(null);
const toast = useToast();

const form = useForm({
    item_ids: [] as number[],
    operation: null as string | null,
    value: null as string | number | null,
    enabled: null as boolean | null,
    attribute_key: null as string | null,
    attribute_paths: [] as string[],
    name_mode: null as string | null,
    search_phrase: null as string | null,
    replacement_phrase: null as string | null,
    prices: [] as RecalculatedPrice[],
});

const bindingOptions: DropdownOption[] = [
    { label: 'Usuń związanie', value: 'none' },
    { label: 'Związany z właścicielem', value: 'isBoundToOwner' },
    { label: 'Związany z właścicielem na stałe', value: 'isPermanentlyBounded' },
    { label: 'Wiąże po założeniu', value: 'isBindsAfterEquip' },
];

const legendaryOptions = computed(() => [
    { label: 'Usuń bonus legendarny', value: 'none' },
    ...props.legendaryBonusOptions,
]);

const lifespanOptions: DropdownOption[] = [
    { label: 'Data wygaśnięcia', value: 'expiresOn' },
    { label: 'Czas do zniknięcia (minuty)', value: 'timeToDisappear' },
];

const nameModeOptions: DropdownOption[] = [
    { label: 'Zamień fragment', value: 'replace' },
    { label: 'Dodaj prefiks', value: 'prefix' },
    { label: 'Dodaj sufiks', value: 'suffix' },
];

const priceAdjustmentOptions: DropdownOption[] = [
    { label: 'Ustaw stałą wartość', value: 'fixed' },
    { label: 'Zmień procentowo', value: 'percentage' },
    { label: 'Pomnóż przez', value: 'multiplier' },
];

const dialogHeader = computed(() => ({
    binding: 'Ustaw związanie',
    boolean_attribute: 'Ustaw atrybut logiczny',
    category: 'Ustaw kategorię',
    clear_attributes: 'Wyczyść atrybuty',
    currency: 'Ustaw walutę',
    legendary_bonus: 'Ustaw bonus legendarny',
    lifespan: 'Ustaw termin ważności',
    name: 'Zmień nazwy',
    price: 'Przelicz wartość',
    price_adjustment: 'Zmień wartość',
    rarity: 'Ustaw rzadkość',
    required_level: 'Ustaw wymagany poziom',
    specific_currency_price: 'Ustaw cenę w walucie specjalnej',
}[props.operation ?? ''] ?? 'Operacja masowa'));

const selectableOptions = computed<DropdownOption[]>(() => ({
    binding: bindingOptions,
    boolean_attribute: props.booleanAttributeOptions,
    category: props.categoryOptions,
    currency: props.currencyOptions,
    legendary_bonus: legendaryOptions.value,
    rarity: props.rarityOptions,
}[props.operation ?? ''] ?? []));

const selectionLabel = computed(() => ({
    binding: 'Sposób związania',
    boolean_attribute: 'Atrybut',
    category: 'Kategoria',
    currency: 'Waluta',
    legendary_bonus: 'Bonus',
    rarity: 'Rzadkość',
}[props.operation ?? ''] ?? 'Wartość'));

const usesDropdownValue = computed(() => [
    'binding',
    'boolean_attribute',
    'category',
    'currency',
    'legendary_bonus',
    'rarity',
].includes(props.operation ?? ''));

const shouldOfferPriceRecalculation = computed(() => [
    'category',
    'currency',
    'rarity',
    'required_level',
].includes(props.operation ?? ''));

const calculatorPrices = computed<RecalculatedPrice[]>(() => props.items.map(item => {
    const attributes = props.operation === 'required_level'
        ? { ...(item.attributes ?? {}), needLevel: numericValue.value }
        : item.attributes;

    return {
        item_id: item.id,
        price: calculateBaseItemPrice({
            category: props.operation === 'category' ? String(selectedValue.value) : item.category,
            rarity: props.operation === 'rarity' ? String(selectedValue.value) : item.rarity,
            currency: props.operation === 'currency' ? String(selectedValue.value) : item.currency,
            attributes,
            attributePoints: item.attribute_points,
            manualAttributePoints: item.manual_attribute_points,
        }),
    };
}));

const adjustedPrices = computed<RecalculatedPrice[]>(() => props.items.map(item => {
    const adjustment = priceAdjustmentValue.value ?? 0;
    let price = item.price ?? 0;

    if (priceAdjustmentMode.value === 'fixed') {
        price = adjustment;
    } else if (priceAdjustmentMode.value === 'percentage') {
        price *= 1 + adjustment / 100;
    } else {
        price *= adjustment;
    }

    return {
        item_id: item.id,
        price: Math.min(1_000_000_000, Math.max(0, Math.round(price))),
    };
}));

const submittedPrices = computed(() => {
    if (props.operation === 'price_adjustment') {
        return adjustedPrices.value;
    }

    const requiredValueIsMissing = props.operation === 'required_level'
        ? numericValue.value === null
        : usesDropdownValue.value && selectedValue.value === null;

    if (props.operation === 'price' || (shouldOfferPriceRecalculation.value && recalculatePrices.value && !requiredValueIsMissing)) {
        return calculatorPrices.value;
    }

    return [];
});

const calculatedPriceRange = computed(() => {
    if (submittedPrices.value.length === 0) {
        return null;
    }

    const prices = submittedPrices.value.map(item => item.price);

    return { min: Math.min(...prices), max: Math.max(...prices) };
});

const canSubmit = computed(() => {
    if (props.items.length === 0 || !props.operation || form.processing) {
        return false;
    }

    if (usesDropdownValue.value) {
        return selectedValue.value !== null;
    }

    if (props.operation === 'specific_currency_price') {
        return clearValue.value || numericValue.value !== null;
    }

    if (props.operation === 'required_level') {
        return numericValue.value !== null;
    }

    if (props.operation === 'lifespan') {
        return clearValue.value || (lifespanAttributeKey.value === 'expiresOn' ? expirationDate.value !== null : numericValue.value !== null);
    }

    if (props.operation === 'clear_attributes') {
        return selectedAttributePaths.value.length > 0;
    }

    if (props.operation === 'name') {
        return nameMode.value === 'replace'
            ? searchPhrase.value.length > 0
            : replacementPhrase.value.length > 0;
    }

    if (props.operation === 'price_adjustment') {
        return priceAdjustmentValue.value !== null;
    }

    return true;
});

watch(
    () => [visible.value, props.operation] as const,
    ([isVisible]) => {
        if (!isVisible) {
            return;
        }

        selectedValue.value = null;
        numericValue.value = null;
        recalculatePrices.value = true;
        enabled.value = true;
        clearValue.value = false;
        lifespanAttributeKey.value = 'expiresOn';
        expirationDate.value = null;
        selectedAttributePaths.value = [];
        nameMode.value = 'replace';
        searchPhrase.value = '';
        replacementPhrase.value = '';
        priceAdjustmentMode.value = 'fixed';
        priceAdjustmentValue.value = null;
        form.clearErrors();
        form.reset();
    },
);

const submit = () => {
    if (!props.operation) {
        return;
    }

    let value = selectedValue.value;
    if (props.operation === 'required_level' || props.operation === 'specific_currency_price') {
        value = numericValue.value;
    }
    if (props.operation === 'specific_currency_price' && clearValue.value) {
        value = null;
    }
    if (props.operation === 'lifespan') {
        value = clearValue.value
            ? null
            : (lifespanAttributeKey.value === 'expiresOn'
                ? Math.floor((expirationDate.value?.getTime() ?? 0) / 1000)
                : numericValue.value);
    }

    form.item_ids = props.items.map(item => item.id);
    form.operation = props.operation === 'price_adjustment' ? 'price' : props.operation;
    form.value = value;
    form.enabled = props.operation === 'boolean_attribute' ? enabled.value : null;
    form.attribute_key = props.operation === 'lifespan' ? lifespanAttributeKey.value : null;
    form.attribute_paths = props.operation === 'clear_attributes' ? selectedAttributePaths.value : [];
    form.name_mode = props.operation === 'name' ? nameMode.value : null;
    form.search_phrase = props.operation === 'name' ? searchPhrase.value : null;
    form.replacement_phrase = props.operation === 'name' ? replacementPhrase.value : null;
    form.prices = submittedPrices.value;

    form.patch(route('base-items.bulk.properties.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Udało się',
                detail: 'Zaktualizowano wybrane przedmioty.',
                life: 3000,
            });
            visible.value = false;
            emit('completed');
            form.reset();
        },
        onError: errors => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: Object.values(errors)[0] ?? 'Nie udało się wykonać operacji masowej.',
                life: 5000,
            });
        },
    });
};
</script>

<template>
    <Dialog v-model:visible="visible" modal :header="dialogHeader" :style="{ width: '38rem' }">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <Message severity="info" :closable="false">
                Operacja obejmie {{ items.length }} wybranych przedmiotów.
            </Message>

            <div v-if="usesDropdownValue" class="flex flex-col gap-2">
                <label for="bulk-base-item-value" class="font-semibold">{{ selectionLabel }}</label>
                <Dropdown
                    v-model="selectedValue"
                    input-id="bulk-base-item-value"
                    :options="selectableOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="`Wybierz: ${selectionLabel.toLocaleLowerCase('pl')}`"
                    :class="{ 'p-invalid': form.errors.value }"
                    filter
                    class="w-full"
                />
            </div>

            <label v-if="operation === 'boolean_attribute'" class="flex cursor-pointer items-center gap-3">
                <Checkbox v-model="enabled" binary />
                <span>Włącz atrybut (odznacz, aby go usunąć)</span>
            </label>

            <Message v-if="operation === 'binding'" severity="warn" :closable="false">
                Wybrany typ zastąpi pozostałe ustawienia związania na tych przedmiotach.
            </Message>

            <Message v-if="operation === 'category'" severity="warn" :closable="false">
                Zmiana kategorii może sprawić, że część obecnych atrybutów nie będzie pasować do przedmiotu.
            </Message>

            <div v-if="operation === 'specific_currency_price'" class="flex flex-col gap-3">
                <label class="font-semibold">Cena w walucie specjalnej</label>
                <InputNumber v-model="numericValue" :min="0" :max="1000000" :disabled="clearValue" class="w-full" />
                <label class="flex cursor-pointer items-center gap-3">
                    <Checkbox v-model="clearValue" binary />
                    <span>Wyczyść cenę specjalną</span>
                </label>
            </div>

            <div v-if="operation === 'required_level'" class="flex flex-col gap-2">
                <label class="font-semibold">Wymagany poziom</label>
                <InputNumber v-model="numericValue" :min="1" :max="300" class="w-full" />
            </div>

            <div v-if="operation === 'lifespan'" class="flex flex-col gap-3">
                <label class="font-semibold">Rodzaj terminu</label>
                <Dropdown
                    v-model="lifespanAttributeKey"
                    :options="lifespanOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                />
                <Calendar
                    v-if="lifespanAttributeKey === 'expiresOn'"
                    v-model="expirationDate"
                    show-time
                    show-icon
                    date-format="dd.mm.yy"
                    :disabled="clearValue"
                    class="w-full"
                />
                <InputNumber
                    v-else
                    v-model="numericValue"
                    :min="0"
                    :disabled="clearValue"
                    suffix=" min"
                    class="w-full"
                />
                <label class="flex cursor-pointer items-center gap-3">
                    <Checkbox v-model="clearValue" binary />
                    <span>Usuń wybrany termin z przedmiotów</span>
                </label>
            </div>

            <div v-if="operation === 'clear_attributes'" class="flex flex-col gap-2">
                <label class="font-semibold">Atrybuty do usunięcia</label>
                <MultiSelect
                    v-model="selectedAttributePaths"
                    :options="clearAttributeGroups"
                    option-group-label="label"
                    option-group-children="options"
                    option-label="label"
                    option-value="value"
                    display="chip"
                    filter
                    placeholder="Wybierz atrybuty"
                    class="w-full"
                />
                <Message severity="warn" :closable="false">Ta operacja usuwa wskazane dane z zaznaczonych przedmiotów.</Message>
            </div>

            <div v-if="operation === 'name'" class="flex flex-col gap-3">
                <label class="font-semibold">Sposób zmiany</label>
                <Dropdown
                    v-model="nameMode"
                    :options="nameModeOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                />
                <InputText
                    v-if="nameMode === 'replace'"
                    v-model="searchPhrase"
                    placeholder="Fragment do zamiany"
                    class="w-full"
                />
                <InputText
                    v-model="replacementPhrase"
                    :placeholder="nameMode === 'replace' ? 'Nowy fragment (może być pusty)' : 'Tekst do dodania'"
                    class="w-full"
                />
            </div>

            <div v-if="operation === 'price_adjustment'" class="flex flex-col gap-3">
                <label class="font-semibold">Sposób zmiany wartości</label>
                <Dropdown
                    v-model="priceAdjustmentMode"
                    :options="priceAdjustmentOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                />
                <InputNumber
                    v-model="priceAdjustmentValue"
                    :min="priceAdjustmentMode === 'percentage' ? -100 : 0"
                    :max="priceAdjustmentMode === 'fixed' ? 1000000000 : 1000"
                    :max-fraction-digits="priceAdjustmentMode === 'multiplier' ? 3 : 0"
                    :suffix="priceAdjustmentMode === 'percentage' ? '%' : undefined"
                    class="w-full"
                />
            </div>

            <label
                v-if="shouldOfferPriceRecalculation"
                class="flex cursor-pointer items-center gap-3"
            >
                <Checkbox v-model="recalculatePrices" binary />
                <span>Przelicz wartość po zmianie według wzoru z edycji przedmiotu</span>
            </label>

            <Message v-if="submittedPrices.length > 0" severity="secondary" :closable="false">
                Każdy przedmiot otrzyma indywidualnie wyliczoną wartość.
                <template v-if="calculatedPriceRange">
                    Zakres po zmianie: {{ calculatedPriceRange.min }}–{{ calculatedPriceRange.max }}.
                </template>
            </Message>

            <small v-for="(error, key) in form.errors" :key="key" class="text-red-500">{{ error }}</small>
        </form>

        <template #footer>
            <Button label="Anuluj" severity="secondary" outlined :disabled="form.processing" @click="visible = false" />
            <Button label="Zapisz" icon="pi pi-check" :disabled="!canSubmit" :loading="form.processing" @click="submit" />
        </template>
    </Dialog>
</template>
