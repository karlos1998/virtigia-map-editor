<script setup lang="ts">
import {route} from "ziggy-js";
import {BaseItemWithRelations} from "@/Resources/BaseItem.resource";
import AppLayout from "../../layout/AppLayout.vue";
import ItemHeader from "../../Components/ItemHeader.vue";
import {router, useForm} from "@inertiajs/vue3";
import { onMounted, ref, computed, watch } from 'vue';
import {useToast} from "primevue";
import axios from "axios";
import AttributeEditor from "../../Components/AttributeEditor.vue";
import AttributePointsEditor from './Components/AttributePointsEditor.vue';
import TeleportToEditor from './Components/TeleportToEditor.vue';
import OutfitEditor from './Components/OutfitEditor.vue';
import PetEditor from './Components/PetEditor.vue';
import {calculateBaseItemPrice} from './BaseItemPriceCalculator';
import JsonEditorVue from 'json-editor-vue'

const props = defineProps<{
    baseItem: BaseItemWithRelations,
}>();

// Function to clean corrupted attributes (removes numeric string keys from JSON editor corruption)
const cleanAttributes = (attrs: any): any => {
    if (!attrs || typeof attrs !== 'object' || Array.isArray(attrs)) {
        return {};
    }

    const cleaned: any = {};
    for (const [key, value] of Object.entries(attrs)) {
        // Skip numeric string keys (these are from corruption)
        if (!/^\d+$/.test(key)) {
            cleaned[key] = value;
        }
    }

    return cleaned;
};

// Create form with full baseItem structure - clean attributes on init
const form = useForm({
    attributes: cleanAttributes(props.baseItem.attributes),
    attribute_points: props.baseItem.attribute_points || {},
    manual_attribute_points: props.baseItem.manual_attribute_points || {}
})

// Local copy of attributes for JSON editor to prevent corruption
const jsonEditorAttributes = ref(JSON.parse(JSON.stringify(cleanAttributes(props.baseItem.attributes))));

// Check if this is a pet/book item
const isPet = computed(() => props.baseItem.category === 'pets');
const isBook = computed(() => props.baseItem.category === 'books');
const isMusicBox = computed(() => props.baseItem.category === 'musicBoxes');
const baseItem = computed(() => props.baseItem);
const persistedPrice = ref(Number(props.baseItem.price ?? 0));
const price = ref<number | null>(Number(props.baseItem.price ?? 0));
const isSavingPrice = ref(false);

const normalizePrice = (value: number | null): number => {
    const numericValue = Number(value ?? 0);

    if (!Number.isFinite(numericValue)) {
        return 0;
    }

    return Math.min(1_000_000_000, Math.max(0, Math.round(numericValue)));
};

const calculatedPrice = computed(() => calculateBaseItemPrice({
    category: baseItem.value.category,
    rarity: baseItem.value.rarity,
    currency: baseItem.value.currency,
    attributes: form.attributes,
    attributePoints: form.attribute_points,
    manualAttributePoints: form.manual_attribute_points,
}));

const isPriceChanged = computed(() => normalizePrice(price.value) !== persistedPrice.value);
const priceCurrencyLabel = computed(() => baseItem.value.currency_name || baseItem.value.currency);

// Set default active tab based on category
const activeTab = ref(isPet.value ? '4' : '0');

// Store scale result from AttributePointsEditor
const scaleResult = ref<any>(null);
const filteredBooks = ref<any[]>([]);
const selectedBook = ref<any | null>(null);
const filteredAudios = ref<any[]>([]);
const selectedAudio = ref<any | null>(null);

async function loadSelectedBookById(bookId: number) {
    if (!bookId || bookId <= 0) {
        selectedBook.value = null;
        return;
    }

    try {
        const { data } = await axios.get(route('books.fetch', { book: bookId }));
        selectedBook.value = {
            id: Number(data?.id ?? bookId),
            title: String(data?.title ?? `#${bookId}`),
        };
    } catch {
        selectedBook.value = { id: bookId, title: `#${bookId}` };
    }
}

async function loadSelectedAudioById(audioId: number) {
    if (!audioId || audioId <= 0) {
        selectedAudio.value = null;
        return;
    }

    try {
        const { data } = await axios.get(route('audios.fetch', { audio: audioId }));
        selectedAudio.value = {
            id: Number(data?.id ?? audioId),
            name: String(data?.name ?? `#${audioId}`),
        };
    } catch {
        selectedAudio.value = { id: audioId, name: `#${audioId}` };
    }
}

// Handle scale result changes from AttributePointsEditor
const handleScaleResultChanged = (result: any) => {
    scaleResult.value = result;
};

// Computed property for tooltip - merge original attributes with scaled attributes
const tooltipAttributes = computed(() => {
    const baseAttributes = { ...form.attributes };

    // If we have scale result, merge it with base attributes
    if (scaleResult.value) {
        return {
            ...scaleResult.value
        };
    }

    return baseAttributes;
});

const toast = useToast();

onMounted(() => {
    toast.add({ severity: 'warn', summary: 'Uwaga', detail: 'W strefie pakowania jest artykuł, który nie powinien się tam znaleźć', life: 10000 });
    const currentBookId = Number(form.attributes?.bookId ?? 0);
    if (isBook.value && currentBookId > 0) {
        loadSelectedBookById(currentBookId);
    }
    const currentAudioId = Number(form.attributes?.audioId ?? 0);
    if (isMusicBox.value && currentAudioId > 0) {
        loadSelectedAudioById(currentAudioId);
    }
})

// Watch for tab changes to sync JSON editor
watch(activeTab, (newTab, oldTab) => {
    // When entering JSON editor tab, sync from form.attributes
    if (newTab === '3') {
        jsonEditorAttributes.value = JSON.parse(JSON.stringify(form.attributes));
    }

    // When leaving JSON editor tab, sync to form.attributes (with validation)
    if (oldTab === '3' && newTab !== '3') {
        syncFromJsonEditor();
    }
});

// Manual sync function from JSON editor
const syncFromJsonEditor = () => {
    try {
        // Validate that attributes is a proper object, not corrupted
        if (jsonEditorAttributes.value && typeof jsonEditorAttributes.value === 'object' && !Array.isArray(jsonEditorAttributes.value)) {
            // Check if it looks corrupted (has numeric string keys like '0', '1', '2', '3')
            const keys = Object.keys(jsonEditorAttributes.value);
            const hasNumericKeys = keys.some(key => /^\d+$/.test(key));

            if (hasNumericKeys) {
                toast.add({
                    severity: 'warn',
                    summary: 'Uwaga',
                    detail: 'Dane w edytorze JSON wyglądają na uszkodzone. Nie zsynchronizowano zmian.',
                    life: 5000
                });
                return false;
            } else {
                form.attributes = JSON.parse(JSON.stringify(jsonEditorAttributes.value));
                toast.add({
                    severity: 'success',
                    summary: 'Sukces',
                    detail: 'Zmiany z edytora JSON zostały zsynchronizowane',
                    life: 3000
                });
                return true;
            }
        }
    } catch (error) {
        console.error('Error syncing from JSON editor:', error);
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: 'Nie udało się zsynchronizować danych z edytora JSON',
            life: 3000
        });
        return false;
    }
};

const getRequestErrorMessage = (error: any, fallback: string): string => {
    const errors = error?.response?.data?.errors ?? {};
    const firstError = Object.values(errors)[0];

    if (Array.isArray(firstError)) {
        return String(firstError[0] ?? fallback);
    }

    if (typeof firstError === 'string') {
        return firstError;
    }

    return error?.response?.data?.message ?? fallback;
};

const saveBasePrice = async (showToast = true): Promise<boolean> => {
    const normalizedPrice = normalizePrice(price.value);
    price.value = normalizedPrice;

    if (normalizedPrice === persistedPrice.value) {
        return true;
    }

    isSavingPrice.value = true;

    try {
        await axios.patch(route('base-items.update', {baseItem: baseItem.value.id}), {
            name: baseItem.value.name,
            category: baseItem.value.category,
            rarity: baseItem.value.rarity,
            currency: baseItem.value.currency,
            price: normalizedPrice,
        });

        persistedPrice.value = normalizedPrice;

        if (showToast) {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Cena przedmiotu została zapisana',
                life: 3000
            });
        }

        return true;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: getRequestErrorMessage(error, 'Nie udało się zapisać ceny przedmiotu'),
            life: 5000
        });

        return false;
    } finally {
        isSavingPrice.value = false;
    }
};

const calculatePrice = () => {
    price.value = calculatedPrice.value;
    toast.add({
        severity: 'info',
        summary: 'Wyliczono cenę',
        detail: `Proponowana wartość: ${calculatedPrice.value} ${priceCurrencyLabel.value}`,
        life: 3000
    });
};

const savePrice = async () => {
    await saveBasePrice(true);
};

const save = async () => {
    const isPriceSaved = await saveBasePrice(false);

    if (!isPriceSaved) {
        return;
    }

    // Prepare the final attributes by merging current attributes with scale result
    let finalAttributes = { ...form.attributes };

    // If we have scale result, merge it with current attributes
    // Scale result values take priority over current attributes, but preserve special fields
    if (scaleResult.value) {
        // Merge scale result, but preserve special attributes that shouldn't be overwritten
        // like teleportTo, useOutfit, description, and any other non-numeric attributes
        const specialAttributes: any = {};

        // Preserve teleportTo if it exists in form.attributes
        if (form.attributes.teleportTo) {
            specialAttributes.teleportTo = form.attributes.teleportTo;
        }

        // Preserve outfit-related attributes if they exist in form.attributes
        if (form.attributes.useOutfit) {
            specialAttributes.useOutfit = form.attributes.useOutfit;
        }
        if (form.attributes.description) {
            specialAttributes.description = form.attributes.description;
        }

        // Preserve pet-related attributes if they exist in form.attributes
        if (form.attributes.petSrc) {
            specialAttributes.petSrc = form.attributes.petSrc;
        }
        if (form.attributes.petActions) {
            specialAttributes.petActions = form.attributes.petActions;
        }

        // First apply scaled attributes, then overlay special attributes
        finalAttributes = {
            ...form.attributes,
            ...scaleResult.value,
            ...specialAttributes  // Special attributes take priority
        };
    }

    if (isBook.value) {
        if (selectedBook.value?.id) {
            finalAttributes.bookId = selectedBook.value.id;
        } else {
            delete finalAttributes.bookId;
        }
    }
    if (isMusicBox.value) {
        if (selectedAudio.value?.id) {
            finalAttributes.audioId = selectedAudio.value.id;
        } else {
            delete finalAttributes.audioId;
        }
    }

    // Create update data with all three fields
    const updateData = {
        attributes: finalAttributes,
        attribute_points: (isBook.value || isMusicBox.value) ? {} : (form.attribute_points || {}),
        manual_attribute_points: (isBook.value || isMusicBox.value) ? {} : (form.manual_attribute_points || {})
    };

    // Send the update
    form
        .transform(() => updateData)
        .patch(route('base-items.attributes.update', { baseItem: baseItem.value }), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Sukces',
                    detail: 'Przedmiot został zapisany z przeskalowanymi atrybutami',
                    life: 3000
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Błąd',
                    detail: 'Nie udało się zapisać',
                    life: 3000
                });
            }
        })
}

const searchBooks = async ({ query }: { query: string }) => {
    const { data } = await axios.get(route('books.search', { query }));
    filteredBooks.value = data;
};

const searchAudios = async ({ query }: { query: string }) => {
    const { data } = await axios.get(route('audios.search', { query }));
    filteredAudios.value = data;
};

const specificCurrencyPrice = ref(props.baseItem.specific_currency_price ?? null);

watch(
    () => props.baseItem,
    (updatedBaseItem) => {
        form.attributes = cleanAttributes(updatedBaseItem.attributes);
        form.attribute_points = updatedBaseItem.attribute_points || {};
        form.manual_attribute_points = updatedBaseItem.manual_attribute_points || {};
        persistedPrice.value = Number(updatedBaseItem.price ?? 0);
        price.value = Number(updatedBaseItem.price ?? 0);
        specificCurrencyPrice.value = updatedBaseItem.specific_currency_price ?? null;

        if (activeTab.value === '3') {
            jsonEditorAttributes.value = JSON.parse(JSON.stringify(cleanAttributes(updatedBaseItem.attributes)));
        }
        const currentBookId = Number(updatedBaseItem.attributes?.bookId ?? 0);
        if (currentBookId > 0) {
            loadSelectedBookById(currentBookId);
        } else {
            selectedBook.value = null;
        }
        const currentAudioId = Number(updatedBaseItem.attributes?.audioId ?? 0);
        if (currentAudioId > 0) {
            loadSelectedAudioById(currentAudioId);
        } else {
            selectedAudio.value = null;
        }
    },
    { deep: true }
);

const saveCurrency = () => {
    router.patch(route('base-items.update', { baseItem: baseItem.value.id }), {
        specific_currency_price: specificCurrencyPrice.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({severity: 'success', summary: 'Sukces', detail: 'Cena waluty zaktualizowana', life: 3000});
        },
        onError: (errors) => {
            toast.add({severity: 'error', summary: 'Błąd', detail: Object.values(errors)[0], life: 5000});
        },
    });
};
const clearCurrency = () => {
    specificCurrencyPrice.value = null;
};

</script>

<template>
    <AppLayout>

        <ItemHeader
            :route-back="route('base-items.show', {baseItem})"
            route-back-label="Powrót do podglądu"
        >
            <template #header>
                #{{ baseItem.id }} - {{ baseItem.name }}

            </template>

            <template #right-buttons>
                <button
                    class="px-4 py-2 text-white bg-green-500 hover:bg-green-600 rounded shadow mr-2"
                    @click="save"
                    :disabled="form.processing || isSavingPrice"
                >
                    <i class="pi pi-save mr-2"></i>
                    Zapisz
                </button>
            </template>
        </ItemHeader>
        <div class="card">
            <img
                class="h-12 w-12 object-cover"
                :src="baseItem.src"
                v-tip.item.top.show-id="{...baseItem, attributes: tooltipAttributes}"
                alt=""
            /> ^ Podgląd edytowanego przedmiotu
            <div v-if="scaleResult" class="mt-2 text-sm text-green-600">
                Wyświetlane są przeskalowane atrybuty z kalkulatora punktów
            </div>
        </div>
        <div class="card my-4 p-3">
            <h4 class="font-semibold mb-2">Cena przedmiotu</h4>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1">
                    <label for="base-item-price" class="text-sm font-medium text-surface-700">Wartość</label>
                    <InputNumber
                        input-id="base-item-price"
                        v-model="price"
                        :min="0"
                        :max="1000000000"
                        :step="1000"
                        show-buttons
                    />
                </div>
                <Button label="Wylicz cenę" icon="pi pi-calculator" severity="info" @click="calculatePrice"/>
                <Button
                    label="Zapisz cenę"
                    icon="pi pi-save"
                    severity="success"
                    @click="savePrice"
                    :loading="isSavingPrice"
                    :disabled="!isPriceChanged || isSavingPrice"
                />
            </div>
            <div class="mt-2 text-xs text-gray-700">
                Aktualnie zapisana wartość: <strong>{{ persistedPrice }}</strong> {{ priceCurrencyLabel }}.
                Propozycja z aktualnych pól: <strong>{{ calculatedPrice }}</strong> {{ priceCurrencyLabel }}.
            </div>
        </div>
        <div class="card my-4 p-3">
            <h4 class="font-semibold mb-2">Specyficzna cena waluty (dla tego przedmiotu, INT, opcjonalna)</h4>
            <div class="flex items-center gap-3">
                <InputNumber v-model="specificCurrencyPrice" :min="0" :max="1000000" placeholder="Wprowadź cenę..."/>
                <Button label="Usuń wartość" text severity="secondary" @click="clearCurrency"
                        v-if="specificCurrencyPrice !== null"/>
                <Button label="Zapisz" icon="pi pi-save" class="p-button-success" @click="saveCurrency"/>
            </div>
            <div v-if="baseItem.specific_currency_price !== null" class="mt-2 text-xs text-gray-700">Aktualna cena
                waluty dla tego itemu: <strong>{{ baseItem.specific_currency_price }}</strong></div>
        </div>
        <div v-if="isBook" class="card">
            <h4 class="font-semibold mb-3">Książka przypięta do przedmiotu</h4>
            <AutoComplete
                class="w-full"
                v-model="selectedBook"
                :suggestions="filteredBooks"
                @complete="searchBooks"
                :option-label="(book: any|null) => book ? `[${book.id}] ${book.title}` : ''"
                placeholder="Wyszukaj książkę po tytule"
                fluid
            />
            <div class="mt-2 text-sm text-surface-500">
                Dla kategorii <strong>books</strong> aktywna jest tylko treść książki (atrybut <code>bookId</code>).
            </div>
        </div>
        <div v-else-if="isMusicBox" class="card">
            <h4 class="font-semibold mb-3">Plik audio przypięty do przedmiotu</h4>
            <AutoComplete
                class="w-full"
                v-model="selectedAudio"
                :suggestions="filteredAudios"
                @complete="searchAudios"
                :option-label="(audio: any|null) => audio ? `[${audio.id}] ${audio.name}` : ''"
                placeholder="Wyszukaj plik audio po nazwie"
                fluid
            />
            <div class="mt-2 text-sm text-surface-500">
                Dla kategorii <strong>musicBoxes</strong> aktywny jest atrybut <code>audioId</code>.
            </div>
        </div>

        <Tabs v-else v-model:value="activeTab" class="card">
            <TabList>
                <Tab v-if="!isPet" value="0">Kalkulator punktów</Tab>
                <Tab v-if="!isPet" value="1">Teleport</Tab>
                <Tab v-if="!isPet" value="2">Strój (Outfit)</Tab>
                <Tab v-if="isPet" value="4">Edycja zwierzaka</Tab>
                <Tab value="3">Edytor json</Tab>
                <!--                <Tab value="5">Edytor atrybutów</Tab>-->
            </TabList>
            <TabPanels>
                <TabPanel v-if="!isPet" value="0">
                    <AttributePointsEditor
                        v-model="form"
                        :base-item="baseItem"
                        @scale-result-changed="handleScaleResultChanged"
                    />
                </TabPanel>
                <TabPanel v-if="!isPet" value="1">
                    <TeleportToEditor v-model:attributes="form.attributes" />
                </TabPanel>
                <TabPanel v-if="!isPet" value="2">
                    <OutfitEditor v-model:attributes="form.attributes" />
                </TabPanel>
                <TabPanel v-if="isPet" value="4">
                    <PetEditor v-model:attributes="form.attributes" :base-item="baseItem" />
                </TabPanel>
                <TabPanel value="3">
                    <JsonEditorVue
                        v-model="jsonEditorAttributes"
                        v-bind="{/* local props & attrs */}"
                    />
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-yellow-800 text-sm">
                            <i class="pi pi-exclamation-triangle mr-2"></i>
                            <strong>Uwaga:</strong> Zmiany w edytorze JSON zostaną zsynchronizowane po przełączeniu na
                            inną zakładkę lub automatycznie.
                            Upewnij się, że edytujesz poprawny JSON (obiekt, nie tablica ani string).
                        </p>
                    </div>
                </TabPanel>
                <!-- <TabPanel value="5">
                    <AttributeEditor v-model:attributes="form.attributes" />
                </TabPanel> -->
            </TabPanels>
        </Tabs>
    </AppLayout>
</template>
