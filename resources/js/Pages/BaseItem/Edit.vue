<script setup lang="ts">
import {route} from "ziggy-js";
import {BaseItemWithRelations} from "@/Resources/BaseItem.resource";
import AppLayout from "../../layout/AppLayout.vue";
import ItemHeader from "../../Components/ItemHeader.vue";
import {router, useForm} from "@inertiajs/vue3";
import { onMounted, ref, computed, watch } from 'vue';
import {useToast} from "primevue";
import AttributeEditor from "../../Components/AttributeEditor.vue";
import AttributePointsEditor from './Components/AttributePointsEditor.vue';
import TeleportToEditor from './Components/TeleportToEditor.vue';
import OutfitEditor from './Components/OutfitEditor.vue';
import JsonEditorVue from 'json-editor-vue'

const { baseItem } = defineProps<{
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
    attributes: cleanAttributes(baseItem.attributes),
    attribute_points: baseItem.attribute_points || {},
    manual_attribute_points: baseItem.manual_attribute_points || {}
})

// Local copy of attributes for JSON editor to prevent corruption
const jsonEditorAttributes = ref(JSON.parse(JSON.stringify(cleanAttributes(baseItem.attributes))));
const activeTab = ref('0');

// Store scale result from AttributePointsEditor
const scaleResult = ref<any>(null);

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

const save = () => {
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

        // First apply scaled attributes, then overlay special attributes
        finalAttributes = {
            ...form.attributes,
            ...scaleResult.value,
            ...specialAttributes  // Special attributes take priority
        };
    }

    // Create update data with all three fields
    const updateData = {
        attributes: finalAttributes,
        attribute_points: form.attribute_points || {},
        manual_attribute_points: form.manual_attribute_points || {}
    };

    // Send the update
    form
        .transform(() => updateData)
        .patch(route('base-items.attributes.update', {baseItem}), {
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

const specificCurrencyPrice = ref(baseItem.specific_currency_price ?? null);

const saveCurrency = () => {
    router.patch(route('base-items.update', {baseItem: baseItem.id}), {
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
                    :loading="form.processing"
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
        <Tabs v-model="activeTab" value="0" class="card">
            <TabList>
                <Tab value="0">Kalkulator punktów</Tab>
                <Tab value="1">Teleport</Tab>
                <Tab value="2">Strój (Outfit)</Tab>
                <Tab value="3">Edytor json</Tab>
                <!--                <Tab value="4">Edytor atrybutów</Tab>-->
            </TabList>
            <TabPanels>
                <TabPanel value="0">
                    <AttributePointsEditor
                        v-model="form"
                        :base-item="baseItem"
                        @scale-result-changed="handleScaleResultChanged"
                    />
                </TabPanel>
                <TabPanel value="1">
                    <TeleportToEditor v-model:attributes="form.attributes" />
                </TabPanel>
                <TabPanel value="2">
                    <OutfitEditor v-model:attributes="form.attributes" />
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
                <!-- <TabPanel value="4">
                    <AttributeEditor v-model:attributes="form.attributes" />
                </TabPanel> -->
            </TabPanels>
        </Tabs>
    </AppLayout>
</template>
