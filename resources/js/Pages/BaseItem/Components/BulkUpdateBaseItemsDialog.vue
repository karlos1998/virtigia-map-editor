<script setup lang="ts">
import type { BaseItemResource } from '@/Resources/BaseItem.resource';
import { useForm } from '@inertiajs/vue3';
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import { useToast } from 'primevue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { calculateBaseItemPrice } from '../BaseItemPriceCalculator';

type BulkBaseItemOperation = 'binding' | 'rarity' | 'currency' | 'price';

type DropdownOption = {
    label: string;
    value: string;
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
}>();

const emit = defineEmits<{
    completed: [];
}>();

const visible = defineModel<boolean>('visible', { default: false });
const selectedValue = ref<string | null>(null);
const recalculatePrices = ref(true);
const toast = useToast();

const form = useForm({
    item_ids: [] as number[],
    operation: null as BulkBaseItemOperation | null,
    value: null as string | null,
    prices: [] as RecalculatedPrice[],
});

const bindingOptions: DropdownOption[] = [
    { label: 'Związany z właścicielem', value: 'isBoundToOwner' },
    { label: 'Związany z właścicielem na stałe', value: 'isPermanentlyBounded' },
    { label: 'Wiąże po założeniu', value: 'isBindsAfterEquip' },
];

const dialogHeader = computed(() => {
    switch (props.operation) {
        case 'binding':
            return 'Ustaw związanie';
        case 'rarity':
            return 'Ustaw rzadkość';
        case 'currency':
            return 'Ustaw walutę';
        case 'price':
            return 'Przelicz wartość';
        default:
            return 'Operacja masowa';
    }
});

const selectableOptions = computed(() => {
    switch (props.operation) {
        case 'binding':
            return bindingOptions;
        case 'rarity':
            return props.rarityOptions;
        case 'currency':
            return props.currencyOptions;
        default:
            return [];
    }
});

const selectionLabel = computed(() => {
    switch (props.operation) {
        case 'binding':
            return 'Sposób związania';
        case 'rarity':
            return 'Rzadkość';
        case 'currency':
            return 'Waluta';
        default:
            return '';
    }
});

const selectionPlaceholder = computed(() => `Wybierz: ${selectionLabel.value.toLocaleLowerCase('pl')}`);

const shouldOfferPriceRecalculation = computed(() => (
    props.operation === 'rarity' || props.operation === 'currency'
));

const priceOverrides = computed(() => ({
    rarity: props.operation === 'rarity' ? selectedValue.value : null,
    currency: props.operation === 'currency' ? selectedValue.value : null,
}));

const calculatedPrices = computed<RecalculatedPrice[]>(() => props.items.map(item => ({
    item_id: item.id,
    price: calculateBaseItemPrice({
        category: item.category,
        rarity: priceOverrides.value.rarity ?? item.rarity,
        currency: priceOverrides.value.currency ?? item.currency,
        attributes: item.attributes,
        attributePoints: item.attribute_points,
        manualAttributePoints: item.manual_attribute_points,
    }),
})));

const calculatedPriceRange = computed(() => {
    if (calculatedPrices.value.length === 0) {
        return null;
    }

    const prices = calculatedPrices.value.map(item => item.price);

    return {
        min: Math.min(...prices),
        max: Math.max(...prices),
    };
});

const canSubmit = computed(() => (
    props.items.length > 0
    && props.operation !== null
    && (props.operation === 'price' || selectedValue.value !== null)
    && !form.processing
));

const successMessage = computed(() => {
    switch (props.operation) {
        case 'binding':
            return 'Zaktualizowano związanie wybranych przedmiotów.';
        case 'rarity':
            return 'Zaktualizowano rzadkość wybranych przedmiotów.';
        case 'currency':
            return 'Zaktualizowano walutę wybranych przedmiotów.';
        case 'price':
            return 'Przeliczono wartość wybranych przedmiotów.';
        default:
            return 'Zaktualizowano wybrane przedmioty.';
    }
});

watch(
    () => [visible.value, props.operation] as const,
    ([isVisible]) => {
        if (!isVisible) {
            return;
        }

        selectedValue.value = null;
        recalculatePrices.value = true;
        form.clearErrors();
        form.reset();
    },
);

const submit = () => {
    if (!props.operation) {
        return;
    }

    const includePrices = props.operation === 'price'
        || (shouldOfferPriceRecalculation.value && recalculatePrices.value);

    form.item_ids = props.items.map(item => item.id);
    form.operation = props.operation;
    form.value = selectedValue.value;
    form.prices = includePrices ? calculatedPrices.value : [];

    form.patch(route('base-items.bulk.properties.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Udało się',
                detail: successMessage.value,
                life: 3000,
            });
            visible.value = false;
            emit('completed');
            form.reset();
        },
        onError: (errors) => {
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
    <Dialog
        v-model:visible="visible"
        modal
        :header="dialogHeader"
        :style="{ width: '36rem' }"
    >
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <Message severity="info" :closable="false">
                Operacja obejmie {{ items.length }} wybranych przedmiotów.
            </Message>

            <div v-if="operation !== 'price'" class="flex flex-col gap-2">
                <label for="bulk-base-item-value" class="font-semibold">{{ selectionLabel }}</label>
                <Dropdown
                    v-model="selectedValue"
                    input-id="bulk-base-item-value"
                    :options="selectableOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="selectionPlaceholder"
                    :class="{ 'p-invalid': form.errors.value }"
                    class="w-full"
                />
                <small v-if="form.errors.value" class="text-red-500">
                    {{ form.errors.value }}
                </small>
            </div>

            <Message v-if="operation === 'binding'" severity="warn" :closable="false">
                Wybrany typ zastąpi pozostałe ustawienia związania na tych przedmiotach.
            </Message>

            <label
                v-if="shouldOfferPriceRecalculation"
                for="bulk-base-item-recalculate-prices"
                class="flex cursor-pointer items-center gap-3"
            >
                <Checkbox
                    v-model="recalculatePrices"
                    input-id="bulk-base-item-recalculate-prices"
                    binary
                />
                <span>Przelicz wartość po zmianie według wzoru z edycji przedmiotu</span>
            </label>

            <Message
                v-if="operation === 'price' || (shouldOfferPriceRecalculation && recalculatePrices)"
                severity="secondary"
                :closable="false"
            >
                Każdy przedmiot otrzyma indywidualnie wyliczoną wartość.
                <template v-if="calculatedPriceRange">
                    Zakres po przeliczeniu: {{ calculatedPriceRange.min }}–{{ calculatedPriceRange.max }}.
                </template>
            </Message>

            <small v-if="form.errors.prices" class="text-red-500">
                {{ form.errors.prices }}
            </small>
            <small v-if="form.errors.item_ids" class="text-red-500">
                {{ form.errors.item_ids }}
            </small>
        </form>

        <template #footer>
            <Button
                label="Anuluj"
                severity="secondary"
                outlined
                :disabled="form.processing"
                @click="visible = false"
            />
            <Button
                :label="operation === 'price' ? 'Przelicz i zapisz' : 'Zapisz'"
                icon="pi pi-check"
                :disabled="!canSubmit"
                :loading="form.processing"
                @click="submit"
            />
        </template>
    </Dialog>
</template>
