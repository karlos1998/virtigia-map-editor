<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue';

/*
|--------------------------------------------------------------------------
| Types & Interfaces
|--------------------------------------------------------------------------
*/

interface AttributePoint {
    name: string;
    type: string;
    description: string;
}

interface AttributeData {
    attributePoints: AttributePoint[];
    manualAttributePoints: AttributePoint[];
}

/*
|--------------------------------------------------------------------------
| Component Setup
|--------------------------------------------------------------------------
*/

const form = defineModel<any>({ default: () => ({}) });
const props = defineProps<{
    baseItem: any;
}>();

const emit = defineEmits<{
    scaleResultChanged: [result: any];
}>();

/*
|--------------------------------------------------------------------------
| Reactive State
|--------------------------------------------------------------------------
*/

const toast = useToast();
const isLoading = ref(true);
const isCalculating = ref(false);
const scaleResult = ref<any>(null);
const attributeData = ref<AttributeData>({
    attributePoints: [],
    manualAttributePoints: []
});

/*
|--------------------------------------------------------------------------
| Computed Properties
|--------------------------------------------------------------------------
*/

/**
 * Calculate total attribute points used across all categories
 */
const totalPointsUsed = computed(() => {
    let total = 0;

    // Count regular attribute points
    attributeData.value.attributePoints.forEach(attr => {
        const value = getAttributeValue(attr.name, false);
        if (typeof value === 'number') {
            total += value;
        }
    });

    // Count manual attribute points
    attributeData.value.manualAttributePoints.forEach(attr => {
        const value = getAttributeValue(attr.name, true);
        if (typeof value === 'number') {
            total += value;
        }
    });

    return total;
});

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Get current value for a specific attribute
 */
function getAttributeValue(attributeName: string, isManual: boolean = false): number {
    if (isManual) {
        return form.value?.manual_attribute_points?.[attributeName] || 0;
    }
    return form.value?.attribute_points?.[attributeName] || 0;
}

/**
 * Update attribute value in the form data
 */
function updateAttributeValue(attributeName: string, value: number, isManual: boolean = false): void {
    if (isManual) {
        if (!form.value.manual_attribute_points) {
            form.value.manual_attribute_points = {};
        }
        form.value.manual_attribute_points[attributeName] = value;
    } else {
        if (!form.value.attribute_points) {
            form.value.attribute_points = {};
        }
        form.value.attribute_points[attributeName] = value;
    }
}

/**
 * Build API parameters from current form state and base item data
 */
function buildApiParameters(): Record<string, any> {
    const params: Record<string, any> = {};

    // Add base item parameters
    if (props.baseItem?.need_level) {
        params.lvl = props.baseItem.need_level;
    }
    if (props.baseItem?.category) {
        params.itemCategory = props.baseItem.category;
    }
    if (props.baseItem?.rarity) {
        params.rarity = props.baseItem.rarity;
    }

    // Always include itemProfessions (empty string if no professions)
    params.itemProfessions = props.baseItem?.need_professions?.length > 0
        ? props.baseItem.need_professions.join(',')
        : '';

    // Add attribute points (only values > 0)
    [form.value?.attribute_points, form.value?.manual_attribute_points]
        .filter(Boolean)
        .forEach(attributeSet => {
            Object.entries(attributeSet).forEach(([key, value]) => {
                if (typeof value === 'number' && value > 0) {
                    params[key] = value;
                }
            });
        });

    return params;
}

/**
 * Check if parameters contain any attribute points (not just base item data)
 */
function hasAttributeParameters(params: Record<string, any>): boolean {
    const baseItemKeys = ['lvl', 'itemCategory', 'itemProfessions', 'rarity'];
    return Object.keys(params).some(key => !baseItemKeys.includes(key));
}

/*
|--------------------------------------------------------------------------
| API Functions
|--------------------------------------------------------------------------
*/

/**
 * Fetch available attribute points from API
 */
async function fetchAttributePoints(): Promise<void> {
    try {
        isLoading.value = true;
        const response = await axios.get('/api/base-items/attribute-points');
        attributeData.value = response.data;
    } catch (error) {
        console.error('Error fetching attribute points:', error);
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: 'Nie udało się pobrać punktów atrybutów',
            life: 3000
        });
    } finally {
        isLoading.value = false;
    }
}

/**
 * Calculate scaled attributes based on current form state
 */
async function calculateScaleAttributes(): Promise<void> {
    try {
        isCalculating.value = true;

        const params = buildApiParameters();

        if (hasAttributeParameters(params)) {
            const response = await axios.get('/api/base-items/scale-attributes', { params });
            scaleResult.value = response.data;
            emit('scaleResultChanged', response.data);

            console.log('Scale attributes request:', params);
            console.log('Scale attributes response:', response.data);
        } else {
            scaleResult.value = null;
            emit('scaleResultChanged', null);
        }
    } catch (error) {
        console.error('Error calculating scale attributes:', error);
        scaleResult.value = null;
        emit('scaleResultChanged', null);
        toast.add({
            severity: 'error',
            summary: 'Błąd',
            detail: 'Nie udało się obliczyć przeskalowanych atrybutów',
            life: 3000
        });
    } finally {
        isCalculating.value = false;
    }
}

/*
|--------------------------------------------------------------------------
| User Actions
|--------------------------------------------------------------------------
*/

/**
 * Increment attribute value and recalculate
 */
async function incrementAttribute(attributeName: string, isManual: boolean = false): Promise<void> {
    const currentValue = getAttributeValue(attributeName, isManual);
    updateAttributeValue(attributeName, currentValue + 1, isManual);
    await calculateScaleAttributes();
}

/**
 * Decrement attribute value and recalculate
 */
async function decrementAttribute(attributeName: string, isManual: boolean = false): Promise<void> {
    const currentValue = getAttributeValue(attributeName, isManual);
    if (currentValue > 0) {
        updateAttributeValue(attributeName, currentValue - 1, isManual);
        await calculateScaleAttributes();
    }
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchAttributePoints();
});
</script>

<template>
    <div class="card">
        <!-- Header Section -->
        <header class="mb-6">
            <h3 class="text-xl font-bold text-gray-800">Kalkulator Punktów Atrybutów</h3>
            <p class="text-sm text-gray-600 mt-1">
                Ustaw punkty atrybutów dla przedmiotu. Wartości zostaną zapisane w attribute_points i
                manual_attribute_points.
            </p>

            <!-- Summary Info -->
            <div class="mt-4 p-3 bg-blue-50 rounded-lg flex justify-between items-center">
                <div>
                    <div class="font-semibold text-blue-800">
                        Łączna suma punktów: {{ totalPointsUsed }}
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        Poziom: {{ props.baseItem?.need_level || 'brak' }} |
                        Kategoria: {{ props.baseItem?.category || 'brak' }} |
                        Rzadkość: {{ props.baseItem?.rarity || 'brak' }} |
                        Profesje: {{ props.baseItem?.need_professions?.join(', ') || 'brak' }}
                    </div>
                </div>
                <div v-if="isCalculating" class="flex items-center text-sm text-gray-600">
                    <ProgressSpinner style="width: 16px; height: 16px;" />
                    <span class="ml-2">Obliczanie...</span>
                </div>
            </div>
        </header>

        <!-- Scaled Results Section -->
        <section v-if="scaleResult" class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
            <h4 class="text-lg font-semibold mb-3 text-green-800">Przeskalowane Atrybuty</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                <div v-for="(value, key) in scaleResult" :key="key" class="text-sm">
                    <span class="font-medium">{{ key }}:</span>
                    <span class="text-green-700">{{ value }}</span>
                </div>
            </div>
        </section>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center items-center py-12">
            <ProgressSpinner />
            <span class="ml-3 text-gray-600">Ładowanie atrybutów...</span>
        </div>

        <!-- Attribute Editors -->
        <div v-else class="space-y-8">
            <!-- Regular Attribute Points -->
            <section>
                <h4 class="text-lg font-semibold mb-4 text-blue-600 border-b border-blue-200 pb-2">
                    Punkty Atrybutów
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="attr in attributeData.attributePoints"
                        :key="attr.name"
                        class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm text-gray-800 truncate">{{ attr.name }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ attr.description }}</div>
                        </div>
                        <div class="flex items-center gap-3 ml-4">
                            <Button
                                @click="decrementAttribute(attr.name, false)"
                                icon="pi pi-minus"
                                severity="danger"
                                size="small"
                                text
                                rounded
                                :disabled="getAttributeValue(attr.name, false) <= 0"
                                class="w-8 h-8"
                            />
                            <div class="min-w-[2rem] text-center font-mono text-sm font-semibold">
                                {{ getAttributeValue(attr.name, false) }}
                            </div>
                            <Button
                                @click="incrementAttribute(attr.name, false)"
                                icon="pi pi-plus"
                                severity="success"
                                size="small"
                                text
                                rounded
                                class="w-8 h-8"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Manual Attribute Points -->
            <section>
                <h4 class="text-lg font-semibold mb-4 text-green-600 border-b border-green-200 pb-2">
                    Manualne Punkty Atrybutów
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="attr in attributeData.manualAttributePoints"
                        :key="attr.name"
                        class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm text-gray-800 truncate">{{ attr.name }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ attr.description }}</div>
                        </div>
                        <div class="flex items-center gap-3 ml-4">
                            <Button
                                @click="decrementAttribute(attr.name, true)"
                                icon="pi pi-minus"
                                severity="danger"
                                size="small"
                                text
                                rounded
                                :disabled="getAttributeValue(attr.name, true) <= 0"
                                class="w-8 h-8"
                            />
                            <div class="min-w-[2rem] text-center font-mono text-sm font-semibold">
                                {{ getAttributeValue(attr.name, true) }}
                            </div>
                            <Button
                                @click="incrementAttribute(attr.name, true)"
                                icon="pi pi-plus"
                                severity="success"
                                size="small"
                                text
                                rounded
                                class="w-8 h-8"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Debug Section (Development Only) -->
            <section class="mt-8 pt-6 border-t border-gray-200">
                <details class="mb-3">
                    <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium">
                        🐛 Debug: Attribute Points
                    </summary>
                    <pre
                        class="mt-3 text-xs bg-gray-50 p-3 rounded border overflow-auto">{{ JSON.stringify(form?.attribute_points || {}, null, 2)
                        }}</pre>
                </details>

                <details class="mb-3">
                    <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium">
                        🐛 Debug: Manual Attribute Points
                    </summary>
                    <pre
                        class="mt-3 text-xs bg-gray-50 p-3 rounded border overflow-auto">{{ JSON.stringify(form?.manual_attribute_points || {}, null, 2)
                        }}</pre>
                </details>

                <details>
                    <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium">
                        🐛 Debug: Scale Result
                    </summary>
                    <pre
                        class="mt-3 text-xs bg-gray-50 p-3 rounded border overflow-auto">{{ JSON.stringify(scaleResult || {}, null, 2)
                        }}</pre>
                </details>
            </section>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for debug sections */
pre {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

pre::-webkit-scrollbar {
    height: 6px;
    width: 6px;
}

pre::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 3px;
}

pre::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

pre::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
