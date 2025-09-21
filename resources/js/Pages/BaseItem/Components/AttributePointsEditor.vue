<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';

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

interface BonusValidationResult {
    expected: number;
    actual: number;
    isValid: boolean;
    severity: 'info' | 'warn' | 'error';
    message: string;
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

// Professions options for multiselect
const professionOptions = [
    { value: 'b', label: 'Tancerz ostrzy' },
    { value: 't', label: 'Tropiciel' },
    { value: 'w', label: 'Wojownik' },
    { value: 'p', label: 'Paladyn' },
    { value: 'h', label: 'Łowca' },
    { value: 'm', label: 'Mag' }
];

const selectedProfessions = ref(
    form.value?.attributes?.needProfessions ||
    props.baseItem?.attributes?.needProfessions ||
    []
);

const selectedLevel = ref(
    form.value?.attributes?.needLevel ||
    props.baseItem?.attributes?.needLevel ||
    1
);

/*
|--------------------------------------------------------------------------
| Computed Properties
|--------------------------------------------------------------------------
*/

/**
 * Calculate total attribute points used across all categories
 * Note: Manual attribute points are NOT counted towards bonus validation limit
 */
const totalPointsUsed = computed(() => {
    let total = 0;

    // Count ONLY regular attribute points for bonus validation
    // Manual attribute points don't count towards the limit
    attributeData.value.attributePoints.forEach(attr => {
        const value = getAttributeValue(attr.name, false);
        if (typeof value === 'number') {
            total += value;
        }
    });

    // Manual attribute points are excluded from bonus validation
    // but we'll create a separate computed for display purposes

    return total;
});

/**
 * Calculate total manual attribute points used (for display only)
 */
const totalManualPointsUsed = computed(() => {
    let total = 0;

    attributeData.value.manualAttributePoints.forEach(attr => {
        const value = getAttributeValue(attr.name, true);
        if (typeof value === 'number') {
            total += value;
        }
    });

    return total;
});

/**
 * Validate bonus count according to item rules
 */
const bonusValidation = computed((): BonusValidationResult => {
    const totalBonuses = totalPointsUsed.value;
    const expectedBonuses = getExpectedBonusCount();

    const difference = Math.abs(totalBonuses - expectedBonuses);
    let severity: 'info' | 'warn' | 'error' = 'info';
    let message = '';

    if (totalBonuses === expectedBonuses) {
        severity = 'info';
        message = 'Liczba bonusów jest zgodna z konwencją';
    } else if (difference <= 2) {
        severity = 'warn';
        message = `Liczba bonusów różni się od konwencji o ${difference}`;
    } else {
        severity = 'error';
        message = `Liczba bonusów znacznie odbiega od konwencji (różnica: ${difference})`;
    }

    return {
        expected: expectedBonuses,
        actual: totalBonuses,
        isValid: totalBonuses === expectedBonuses,
        severity,
        message
    };
});

/**
 * Get profession labels for display
 */
const selectedProfessionLabels = computed(() => {
    return selectedProfessions.value
        .map((prof: string) => professionOptions.find(option => option.value === prof)?.label || prof)
        .join(', ') || 'brak';
});

/**
 * Validate profession selection - at least one profession must be selected
 */
const professionValidation = computed(() => {
    const isValid = selectedProfessions.value.length > 0;
    return {
        isValid,
        message: isValid
            ? `Wybrano profesje: ${selectedProfessionLabels.value}`
            : 'Musisz wybrać co najmniej jedną profesję'
    };
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

    // Add base item parameters with proper defaults
    // Level from attributes.needLevel, default to 1 if not present
    params.lvl = selectedLevel.value;

    // Category from main baseItem.category (not from attributes)
    if (props.baseItem?.category) {
        params.itemCategory = props.baseItem.category;
    }

    // Rarity from main baseItem.rarity (not from attributes)
    if (props.baseItem?.rarity) {
        params.rarity = props.baseItem.rarity;
    }

    // Professions from selectedProfessions, default to empty string
    if (selectedProfessions.value.length > 0) {
        params.itemProfessions = selectedProfessions.value.join(',');
    } else {
        params.itemProfessions = '';
    }

    // Add attribute points (only values !== 0)
    [form.value?.attribute_points, form.value?.manual_attribute_points]
        .filter(Boolean)
        .forEach(attributeSet => {
            Object.entries(attributeSet).forEach(([key, value]) => {
                if (typeof value === 'number' && value !== 0) {
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

/**
 * Get expected bonus count
 */
function getExpectedBonusCount(): number {
    // Category and rarity are in main baseItem object, not in attributes
    const category = props.baseItem?.category;
    const rarity = props.baseItem?.rarity;

    if (!category || !rarity) {
        return 0;
    }

    let baseBonuses = 0;

    // 2.1 Zbroje, bronie jednoręczne, półtoręczne, dwuręczne, dystansowe i pomocnicze, kołczany, różdżki, orby i tarcze
    if (['armors', 'oneHanded', 'halfHanded', 'twoHanded', 'distances', 'auxiliary', 'wands', 'shields'].includes(category)) {
        switch (rarity) {
            case 'common':
                baseBonuses = 1;
                break;
            case 'unique':
                baseBonuses = 3;
                break;
            case 'heroic':
                baseBonuses = 6;
                break;
            case 'upgraded':
                baseBonuses = 7;
                break;
            case 'legendary':
                baseBonuses = 9;
                break;
            default:
                baseBonuses = 1;
                break;
        }
    }
    // 2.2 Strzały
    else if (category === 'arrows') {
        switch (rarity) {
            case 'common':
                baseBonuses = 2;
                break;
            case 'unique':
                baseBonuses = 4;
                break;
            case 'heroic':
                baseBonuses = 8;
                break;
            case 'upgraded':
                baseBonuses = 8;
                break;
            case 'legendary':
                baseBonuses = 12;
                break;
            default:
                baseBonuses = 2;
                break;
        }
    }
    // 2.3 Hełmy, rękawice, buty
    else if (['helmets', 'gloves', 'boots'].includes(category)) {
        switch (rarity) {
            case 'common':
                baseBonuses = 1;
                break;
            case 'unique':
                baseBonuses = 4;
                break;
            case 'heroic':
            case 'upgraded':
                baseBonuses = 8;
                break;
            case 'legendary':
                baseBonuses = 12;
                break;
            default:
                baseBonuses = 1;
                break;
        }
    }
    // 2.4 Pierścienie
    else if (category === 'rings') {
        switch (rarity) {
            case 'common':
                baseBonuses = 5;
                break;
            case 'unique':
                baseBonuses = 9;
                break;
            case 'heroic':
            case 'upgraded':
                baseBonuses = 13;
                break;
            case 'legendary':
                baseBonuses = 17;
                break;
            default:
                baseBonuses = 5;
                break;
        }
    }
    // 2.5 Naszyjniki
    else if (category === 'necklaces') {
        switch (rarity) {
            case 'common':
                baseBonuses = 6;
                break;
            case 'unique':
                baseBonuses = 10;
                break;
            case 'heroic':
            case 'upgraded':
                baseBonuses = 14;
                break;
            case 'legendary':
                baseBonuses = 18;
                break;
            default:
                baseBonuses = 6;
                break;
        }
    }
    // 2.6 Błogosławieństwa
    else if (category === 'blessings') {
        switch (rarity) {
            case 'common':
                baseBonuses = 2;
                break;
            case 'unique':
                baseBonuses = 3;
                break;
            case 'heroic':
            case 'upgraded':
            case 'legendary':
                baseBonuses = 4;
                break;
            default:
                baseBonuses = 2;
                break;
        }
    }
    // Other categories - use generic rules
    else {
        switch (rarity) {
            case 'common':
                baseBonuses = 1;
                break;
            case 'unique':
                baseBonuses = 3;
                break;
            case 'heroic':
                baseBonuses = 6;
                break;
            case 'upgraded':
                baseBonuses = 7;
                break;
            case 'legendary':
                baseBonuses = 9;
                break;
            default:
                baseBonuses = 1;
                break;
        }
    }

    // 2.7 Dodatkowe modyfikatory
    let additionalBonuses = 0;

    // Check for special modifiers (these would need to be added to baseItem data)
    // For now, we'll assume standard items without special modifiers
    // In the future, you could add fields like:
    // - isTitanLoot: boolean
    // - isFromBarter: boolean
    // - isQuestReward: boolean
    // - isFromLimitedAuction: boolean
    // - isCursed: boolean

    return baseBonuses + additionalBonuses;
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

        // Check profession validation first
        if (!professionValidation.value.isValid) {
            toast.add({
                severity: 'warn',
                summary: 'Walidacja',
                detail: 'Musisz wybrać co najmniej jedną profesję przed obliczeniem atrybutów',
                life: 3000
            });
            scaleResult.value = null;
            emit('scaleResultChanged', null);
            return;
        }

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
    updateAttributeValue(attributeName, currentValue - 1, isManual);
    await calculateScaleAttributes();
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchAttributePoints();
});

watch(selectedProfessions, async () => {
    // Save to form data
    if (!form.value.attributes) {
        form.value.attributes = {};
    }
    form.value.attributes.needProfessions = selectedProfessions.value;

    await calculateScaleAttributes();
});

watch(selectedLevel, async () => {
    // Save to form data
    if (!form.value.attributes) {
        form.value.attributes = {};
    }
    form.value.attributes.needLevel = selectedLevel.value;

    await calculateScaleAttributes();
});
</script>

<template>
    <div class="card">
        <!-- Header Section -->
        <header class="mb-6">
            <h3 class="text-xl font-bold text-gray-800">Kalkulator Punktów Atrybutów</h3>

            <!-- Summary Info -->
            <div class="mt-4 p-3 bg-blue-50 rounded-lg space-y-3">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="font-semibold text-blue-800">
                            Punkty atrybutów: {{ totalPointsUsed }}
                        </div>
                        <div class="text-xs text-gray-600 mt-1">
                            Poziom: {{ selectedLevel }} |
                            Kategoria: {{ props.baseItem?.category || 'brak' }} |
                            Rzadkość: {{ props.baseItem?.rarity || 'brak' }} |
                            Profesje: {{ selectedProfessionLabels }}
                        </div>
                    </div>
                    <div v-if="isCalculating" class="flex items-center text-sm text-gray-600">
                        <ProgressSpinner style="width: 16px; height: 16px;" />
                        <span class="ml-2">Obliczanie...</span>
                    </div>
                </div>

                <!-- Bonus Validation -->
                <div v-if="bonusValidation.expected > 0"
                     :class="{
                         'bg-green-100 border border-green-200 text-green-800': bonusValidation.severity === 'info',
                         'bg-yellow-100 border border-yellow-200 text-yellow-800': bonusValidation.severity === 'warn',
                         'bg-red-100 border border-red-200 text-red-800': bonusValidation.severity === 'error'
                     }"
                     class="p-3 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i v-if="bonusValidation.severity === 'info'" class="pi pi-check-circle"></i>
                            <i v-else-if="bonusValidation.severity === 'warn'" class="pi pi-exclamation-triangle"></i>
                            <i v-else class="pi pi-times-circle"></i>
                            <span class="font-medium">{{ bonusValidation.message }}</span>
                        </div>
                        <div class="text-sm">
                            Oczekiwane: {{ bonusValidation.expected }} | Aktualne: {{ bonusValidation.actual }}
                        </div>
                    </div>
                </div>


            </div>

            <!-- Profession Selection and Level -->
            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Profession Selection -->
                    <div>
                        <div class="font-semibold text-blue-800 mb-2">Profesje:</div>
                        <MultiSelect v-model="selectedProfessions" :options="professionOptions"
                                     optionLabel="label" optionValue="value"
                                     placeholder="Wybierz profesje"
                                     :class="['w-full', { 'p-invalid': !professionValidation.isValid }]" />

                        <!-- Profession Validation -->
                        <div v-if="!professionValidation.isValid" class="mt-2">
                            <div class="bg-red-100 border border-red-200 text-red-800 p-2 rounded text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-times-circle"></i>
                                    <span class="font-medium">{{ professionValidation.message }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Level Selection -->
                    <div>
                        <div class="font-semibold text-blue-800 mb-2">Poziom:</div>
                        <InputNumber v-model="selectedLevel" showButtons buttonLayout="horizontal" :min="1" :max="1000"
                                     class="w-full" />
                    </div>
                </div>
            </div>

        </header>

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
                        class="flex flex-col p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200"
                    >
                        <div class="mb-3">
                            <div class="font-medium text-sm text-gray-800">{{ attr.name }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ attr.description }}</div>
                        </div>
                        <InputNumber
                            :model-value="getAttributeValue(attr.name, true)"
                            @update:model-value="(value) => updateAttributeValue(attr.name, value || 0, true)"
                            @input="calculateScaleAttributes"
                            showButtons
                            buttonLayout="horizontal"
                            class="w-full"
                        />
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
                <details>
                    <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium">
                        🐛 Debug: Bonus Validation
                    </summary>
                    <pre
                        class="mt-3 text-xs bg-gray-50 p-3 rounded border overflow-auto">{{ JSON.stringify(bonusValidation || {}, null, 2)
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
