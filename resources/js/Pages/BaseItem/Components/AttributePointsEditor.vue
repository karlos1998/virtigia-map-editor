<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';

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

interface AttackElement {
    name: string;
    description: string;
}

interface AttributeData {
    attributePoints: AttributePoint[];
    manualAttributePoints: AttributePoint[];
    attackElements: AttackElement[];
}

interface BonusValidationResult {
    expected: number;
    actual: number;
    isValid: boolean;
    severity: 'info' | 'warn' | 'error';
    message: string;
}

interface BooleanAttribute {
    key: string;
    label: string;
}

interface AdditionalAttribute {
    key: string;
    label: string;
    type: 'int' | 'string' | 'timestamp' | 'text' | 'multiselect' | 'array';
    placeholder?: string;
    showTime?: boolean;
    dateFormat?: string;
    options?: Array<{ label: string; value: string }>;
    arraySize?: number;
    arrayConstraints?: Array<{ min?: number; max?: number }>;
}

interface LegendaryBonusOption {
    name: string;
    label: string;
    value: number;
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
    manualAttributePoints: [],
    attackElements: []
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

const selectedAttackElements = ref(
    form.value?.attributes?.needAttackElements ||
    props.baseItem?.attributes?.needAttackElements ||
    []
);

// Boolean attributes configuration
const booleanAttributes: BooleanAttribute[] = [
    {key: 'isNonStoreableInClanDeposit', label: 'Przedmiotu nie można przechowywać w depozycie klanowym'},
    {key: 'isBindPermanentlyAfterBuy', label: 'Wiąże na stałe po kupieniu'},
    {key: 'isNonStoreableInDeposit', label: 'Przedmiotu nie można przechowywać w depozycie'},
    {key: 'isPermanentlyBounded', label: 'Związany z właścicielem na stałe'},
    {key: 'isBindsAfterEquip', label: 'Wiąże po założeniu'},
    {key: 'isNotAuctionable', label: 'Tego przedmiotu nie można wystawiać na aukcję'},
    {key: 'isBoundToOwner', label: 'Związany z włascicielem'},
    {key: 'isRecovered', label: 'Przedmiot odzyskany, obniżona wartość'},
    {key: 'isUnidentified', label: 'Przedmiot niezidentyfikowany'},
    {key: 'impossibleToRemove', label: 'Czar niemożliwy do zdjęcia'}
];

const additionalAttributes: AdditionalAttribute[] = [
    {key: 'shortenRevival', label: 'Skrócone odrodzenie (sekundy)', type: 'int'},
    {key: 'description', label: 'Opis', type: 'text', placeholder: 'Podaj opis'},
    {key: 'quantity', label: 'Ilość', type: 'int'},
    {key: 'incrementGold', label: 'Zwiększ złoto', type: 'int'},
    {key: 'healRemaining', label: 'Pełne Uleczenie', type: 'int'},
    {key: 'maxQuantity', label: 'Maksimum sztuk razem', type: 'int'},
    {key: 'expiresOn', label: 'Wygasa', type: 'timestamp', showTime: true, dateFormat: 'dd.mm.yy'},
    {key: 'healthRestorationPercent', label: 'Odnowienie % punktów HP', type: 'int'},
    {key: 'bagCapacity', label: 'Pojemność torby', type: 'int'},
    {key: 'restoreHealthPoints', label: 'Leczy punkty HP', type: 'int'},
    {key: 'stamina', label: 'Dodaje wyczerpanie', type: 'int'},
    {key: 'addDraconite', label: 'Dodaje smocze łzy', type: 'int'},
    {key: 'fasterRevivalRecovery', label: 'Procentowe przyśpieszenie wracania do siebie', type: 'int'},
    {key: 'timeToDisappear', label: 'Zniknie za X minut', type: 'int'},
    {key: 'percentageUpgradeCommon', label: 'Ulepszenie przedmiotu zwykłego o %', type: 'int'},
    {key: 'percentageUpgradeUnique', label: 'Ulepszenie przedmiotu unikatowego o %', type: 'int'},
    {key: 'percentageUpgradeHeroic', label: 'Ulepszenie przedmiotu heroicznego o %', type: 'int'},
    {key: 'percentageUpgradeLegendary', label: 'Ulepszenie przedmiotu legendarnego o %', type: 'int'},
    {
        key: 'upgradeableCategories', label: 'Ulepsza', type: 'multiselect', options: [
            {label: 'Jednoręczne', value: 'oneHanded'},
            {label: 'Zbroje', value: 'armors'},
            {label: 'Dwuręczne', value: 'twoHanded'},
            {label: 'Półtoręczne', value: 'halfHanded'},
            {label: 'Rękawice', value: 'gloves'},
            {label: 'Hełmy', value: 'helmets'},
            {label: 'Buty', value: 'boots'},
            {label: 'Pierścienie', value: 'rings'},
            {label: 'Naszyjniki', value: 'necklaces'},
            {label: 'Tarcze', value: 'shields'},
            {label: 'Kostury', value: 'staffs'},
            {label: 'Pomocnicze', value: 'auxiliary'},
            {label: 'Konsumpcyjne', value: 'consumable'},
            {label: 'Różdżki', value: 'wands'},
            {label: 'Dystansowe', value: 'distances'},
            {label: 'Strzały', value: 'arrows'},
        ]
    },
    {key: 'reduceLevelRequirementCommon', label: 'Obniża wymagania zwykłego o', type: 'int'},
    {key: 'reduceLevelRequirementUnique', label: 'Obniża wymagania unikatowego o', type: 'int'},
    {key: 'reduceLevelRequirementHeroic', label: 'Obniża wymagania heroicznego o', type: 'int'},
    {key: 'reduceLevelRequirementLegendary', label: 'Obniża wymagania legendarnego o', type: 'int'},
    {
        key: 'healChanceAfterFight',
        label: 'Szansa na wyleczenie po walce',
        type: 'array',
        arraySize: 2,
        arrayConstraints: [
            {min: 1, max: 99}, // percentage chance 1-99%
            {} // any number for the second value
        ]
    },
];

const legendaryBonuses: LegendaryBonusOption[] = [
    {name: 'none', label: 'Brak', value: 0},
    {
        name: 'angelTouchHealingChance',
        label: 'Dotknięcie anioła',
        value: 5
    },
    {
        name: 'superCriticalHitChance',
        label: 'Cios bardzo krytyczny',
        value: 10
    },
    {name: 'superMagicalReduction', label: 'Ochrona żywiołów', value: 12},
    {name: 'superCriticalReduction', label: 'Krytyczna osłona', value: 15},
    {name: 'superPhysicalReduction', label: 'Fizyczna osłona', value: 12},
    {name: 'curseChanceAfterDoHit', label: 'Klątwa', value: 7},
    {name: 'pushBack', label: 'Odrzut', value: 8},
    {name: 'superHealOnLowHealth', label: 'Ostatni ratunek', value: 30},
];

const selectedLegendaryBonus = ref(
    form.value?.attributes?.legendaryBon &&
    Array.isArray(form.value.attributes.legendaryBon) &&
    legendaryBonuses.find(b => b.name === form.value.attributes.legendaryBon[0])?.name ||
    props.baseItem?.attributes?.legendaryBon &&
    Array.isArray(props.baseItem.attributes.legendaryBon) &&
    legendaryBonuses.find(b => b.name === props.baseItem.attributes.legendaryBon[0])?.name ||
    legendaryBonuses[0].name
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

/**
 * Get attack element labels for display
 */
const selectedAttackElementLabels = computed(() => {
    return selectedAttackElements.value
        .map((attackElement: string) => attributeData.value.attackElements.find(option => option.name === attackElement)?.description || attackElement)
        .join(', ') || 'brak';
});

/**
 * Get count of enabled boolean attributes
 */
const enabledBooleanAttributesCount = computed(() => {
    return booleanAttributes.filter(attr => getBooleanAttributeValue(attr.key)).length;
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
 * Get current value for a boolean attribute
 */
function getBooleanAttributeValue(attributeKey: string): boolean {
    return form.value?.attributes?.[attributeKey] || false;
}

/**
 * Update boolean attribute value in the form data
 */
function updateBooleanAttribute(attributeKey: string, value: boolean): void {
    if (!form.value.attributes) {
        form.value.attributes = {};
    }
    // Modify existing attributes object in place instead of reassigning
    form.value.attributes[attributeKey] = value;
}

/**
 * Update additional attribute value in the form data
 */
function updateAdditionalAttribute(attributeKey: string, value: number | string | Date | null | string[] | number[]): void {
    if (!form.value.attributes) {
        form.value.attributes = {};
    }

    let finalValue = value;
    if (value instanceof Date) {
        finalValue = Math.floor(value.getTime() / 1000);
    }

    // Modify existing attributes object in place instead of reassigning
    form.value.attributes[attributeKey] = finalValue;

    calculateScaleAttributes();
}

/**
 * Get additional attribute value - converts unix timestamp to Date if needed
 */
function getAdditionalAttributeValue(attributeKey: string, type: 'int' | 'string' | 'timestamp' | 'text' | 'multiselect' | 'array'): number | string | Date | string[] | number[] | null {
    const value = form.value?.attributes?.[attributeKey];

    if (value === null || value === undefined) {
        if (type === 'array') {
            const attr = additionalAttributes.find(a => a.key === attributeKey);
            return attr?.arraySize ? new Array(attr.arraySize).fill(0) : [];
        }
        return type === 'int' ? 0 : type === 'string' || type === 'text' ? '' : type === 'multiselect' ? [] : null;
    }

    if (type === 'timestamp' && typeof value === 'number') {
        return new Date(value * 1000);
    }

    return value;
}

/**
 * Get additional attribute value as integer
 */
function getAdditionalAttributeValueAsInt(attributeKey: string): number {
    const value = getAdditionalAttributeValue(attributeKey, 'int');
    return typeof value === 'number' ? value : 0;
}

/**
 * Get additional attribute value as string
 */
function getAdditionalAttributeValueAsString(attributeKey: string): string {
    const value = getAdditionalAttributeValue(attributeKey, 'string');
    return typeof value === 'string' ? value : '';
}

/**
 * Get additional attribute value as text
 */
function getAdditionalAttributeValueAsText(attributeKey: string): string {
    const value = getAdditionalAttributeValue(attributeKey, 'text');
    return typeof value === 'string' ? value : '';
}

/**
 * Get additional attribute value as Date
 */
function getAdditionalAttributeValueAsDate(attributeKey: string): Date | null {
    const value = getAdditionalAttributeValue(attributeKey, 'timestamp');
    return value instanceof Date ? value : null;
}

/**
 * Get array constraints for specific array attribute and index
 */
function getArrayConstraint(attributeKey: string, index: number, constraint: 'min' | 'max'): number | undefined {
    const attr = additionalAttributes.find(a => a.key === attributeKey);
    if (!attr || !attr.arrayConstraints || index >= attr.arrayConstraints.length) {
        return undefined;
    }
    return attr.arrayConstraints[index][constraint];
}

/**
 * Get additional attribute value as array
 */
function getAdditionalAttributeValueAsArray(attributeKey: string): number[] {
    const value = getAdditionalAttributeValue(attributeKey, 'array');
    return Array.isArray(value) ? value : [];
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

    // Attack elements from selectedAttackElements, default to empty string
    if (selectedAttackElements.value.length > 0) {
        params.attackElements = selectedAttackElements.value.join(',');
    } else {
        params.attackElements = '';
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

    // Add boolean attributes
    booleanAttributes.forEach(attr => {
        if (getBooleanAttributeValue(attr.key)) {
            params[attr.key] = true;
        }
    });

    // Add additional attributes
    additionalAttributes.forEach(attr => {
        const value = getAdditionalAttributeValue(attr.key, attr.type);
        if (value !== null && value !== undefined && value !== '') {
            // For arrays, check if all elements are 0 - if so, don't include
            if (attr.type === 'array' && Array.isArray(value)) {
                if (value.every(v => v === 0)) {
                    return; // Skip empty arrays (all zeros)
                }
            }
            // For numbers, don't include if value is 0
            else if (attr.type === 'int' && value === 0) {
                return; // Skip zero values
            }

            if (attr.type === 'timestamp' && value instanceof Date) {
                params[attr.key] = Math.floor(value.getTime() / 1000);
            } else {
                params[attr.key] = value;
            }
        }
    });

    // Add legendary bonus
    if (selectedLegendaryBonus.value !== 'none') {
        const bonus = legendaryBonuses.find(b => b.name === selectedLegendaryBonus.value);
        if (bonus) {
            params.legendaryBonus = [bonus.name, bonus.value];
        }
    }

    return params;
}

/**
 * Check if parameters contain any attribute points (not just base item data)
 */
function hasAttributeParameters(params: Record<string, any>): boolean {
    const baseItemKeys = ['lvl', 'itemCategory', 'itemProfessions', 'attackElements', 'rarity', 'legendaryBonus'];
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

async function loadReverseAttributes() {
    if (!props.baseItem?.reverse_attributes) {
        return;
    }

    const reverseData = props.baseItem.reverse_attributes;

    // Set attribute points
    if (reverseData.attributePoints) {
        form.value.attribute_points = {...reverseData.attributePoints};
    }

    // Set manual attribute points
    if (reverseData.manualAttributePoints) {
        form.value.manual_attribute_points = {...reverseData.manualAttributePoints};
    }

    // Set attack elements if present
    if (reverseData.attackElements && Array.isArray(reverseData.attackElements)) {
        selectedAttackElements.value = [...reverseData.attackElements];
    }

    // await calculateScaleAttributes();
}

/*
|--------------------------------------------------------------------------
| User Actions
|--------------------------------------------------------------------------
*/

/**
 * Reset all attribute points to 0
 */
async function resetAttributePoints(): Promise<void> {
    if (!form.value.attribute_points) {
        return;
    }

    // Reset all attribute points to 0
    Object.keys(form.value.attribute_points).forEach(key => {
        form.value.attribute_points[key] = 0;
    });

    // Recalculate after reset
    await calculateScaleAttributes();

    toast.add({
        severity: 'success',
        summary: 'Reset',
        detail: 'Punkty atrybutów zostały zresetowane',
        life: 3000
    });
}

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

    // Save to form data - preserve existing attributes by modifying in place
    if (!form.value.attributes) {
        form.value.attributes = {};
    }
    // Modify existing attributes object in place instead of reassigning
    form.value.attributes.needProfessions = selectedProfessions.value;


    await calculateScaleAttributes();
});

watch(selectedLevel, async () => {

    // Save to form data - preserve existing attributes by modifying in place
    if (!form.value.attributes) {
        form.value.attributes = {};
    }
    // Modify existing attributes object in place instead of reassigning
    form.value.attributes.needLevel = selectedLevel.value;


    await calculateScaleAttributes();
});

watch(selectedAttackElements, async () => {

    // Save to form data - preserve existing attributes by modifying in place
    if (!form.value.attributes) {
        form.value.attributes = {};
    }
    // Modify existing attributes object in place instead of reassigning
    form.value.attributes.needAttackElements = selectedAttackElements.value;


    await calculateScaleAttributes();
});

watch(selectedLegendaryBonus, async () => {

    // Save to form data - preserve existing attributes by modifying in place
    if (!form.value.attributes) {
        form.value.attributes = {};
    }

    let legendaryBonValue = null;
    if (selectedLegendaryBonus.value !== 'none') {
        const bonus = legendaryBonuses.find(b => b.name === selectedLegendaryBonus.value);
        if (bonus) {
            legendaryBonValue = [bonus.name, bonus.value];
        }
    }

    // Modify existing attributes object in place instead of reassigning
    form.value.attributes.legendaryBon = legendaryBonValue;


    await calculateScaleAttributes();
});

/*
|--------------------------------------------------------------------------
| Template
|--------------------------------------------------------------------------
*/

</script>

<template>
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
                        Profesje: {{ selectedProfessionLabels }} |
                        Elementy ataku: {{ selectedAttackElementLabels }} |
                        Atrybuty logiczne: {{ enabledBooleanAttributesCount }}
                    </div>
                </div>
                <div class="flex gap-2">
                    <div v-if="isCalculating" class="flex items-center text-sm text-gray-600">
                        <ProgressSpinner style="width: 16px; height: 16px;"/>
                        <span class="ml-2">Obliczanie...</span>
                    </div>
                    <Button
                        v-if="props.baseItem?.reverse_attributes"
                        @click="loadReverseAttributes"
                        icon="pi pi-bolt"
                        severity="info"
                        size="small"
                        label="Debug: Wczytaj reverse attributes"
                    />
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
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
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

                <!-- Attack Element Selection -->
                <div>
                    <div class="font-semibold text-blue-800 mb-2">Elementy ataku:</div>
                    <MultiSelect v-model="selectedAttackElements" :options="attributeData.attackElements"
                                 optionLabel="description" optionValue="name"
                                 placeholder="Wybierz elementy ataku"
                                 class="w-full"/>
                </div>

                <!-- Level Selection -->
                <div>
                    <div class="font-semibold text-blue-800 mb-2">Poziom:</div>
                    <InputNumber v-model="selectedLevel" showButtons buttonLayout="horizontal" :min="1" :max="1000"
                                 class="w-full" />
                </div>

                <!-- Legendary Bonus Selection -->
                <div>
                    <div class="font-semibold text-blue-800 mb-2">Bonus legendarny:</div>
                    <Dropdown v-model="selectedLegendaryBonus" :options="legendaryBonuses" optionLabel="label"
                              optionValue="name"
                              placeholder="Wybierz bonus legendarny" class="w-full"/>
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
            <h4 class="text-lg font-semibold mb-4 text-blue-600 border-b border-blue-200 pb-2 flex justify-between items-center">
                Punkty Atrybutów
                <Button @click="resetAttributePoints" icon="pi pi-refresh" severity="success" size="small" text
                        rounded class="h-8 ml-2" label="Resetuj" />
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
                        @update:model-value="(value: number | null) => updateAttributeValue(attr.name, value || 0, true)"
                        @input="calculateScaleAttributes"
                        showButtons
                        buttonLayout="horizontal"
                        class="w-full"
                    />
                </div>
            </div>
        </section>

        <!-- Boolean Attributes -->
        <section>
            <h4 class="text-lg font-semibold mb-4 text-blue-600 border-b border-blue-200 pb-2 flex justify-between items-center">
                Atrybuty logiczne
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="attr in booleanAttributes"
                    :key="attr.key"
                    :class="[
                        'flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200 active:ring-2 active:ring-blue-400 cursor-pointer',
                        {'bg-blue-100 border-blue-200': getBooleanAttributeValue(attr.key)}
                    ]"
                    @click="updateBooleanAttribute(attr.key, !getBooleanAttributeValue(attr.key))"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm text-gray-800 truncate">{{ attr.label }}</div>
                    </div>
                    <div class="flex items-center gap-3 ml-4">
                        <Checkbox
                            :modelValue="getBooleanAttributeValue(attr.key)"
                            :binary="true"
                            class="mr-2"
                            style="pointer-events: none"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Additional Attributes -->
        <section>
            <h4 class="text-lg font-semibold mb-4 text-blue-600 border-b border-blue-200 pb-2 flex justify-between items-center">
                Dodatkowe Atrybuty
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="attr in additionalAttributes"
                    :key="attr.key"
                    class="flex flex-col p-4 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200"
                >
                    <div class="mb-3">
                        <div class="font-medium text-sm text-gray-800">{{ attr.label }}</div>
                    </div>
                    <template v-if="attr.type === 'int'">
                        <InputNumber
                            :model-value="getAdditionalAttributeValueAsInt(attr.key)"
                            @update:model-value="(value: number | null) => updateAdditionalAttribute(attr.key, value || 0)"
                            showButtons
                            buttonLayout="horizontal"
                            class="w-full"
                        />
                    </template>
                    <template v-else-if="attr.type === 'string'">
                        <InputText
                            :model-value="getAdditionalAttributeValueAsString(attr.key)"
                            @update:model-value="(value: string) => updateAdditionalAttribute(attr.key, value)"
                            class="w-full"
                            :placeholder="attr.placeholder"
                        />
                    </template>
                    <template v-else-if="attr.type === 'text'">
                        <Textarea
                            :model-value="getAdditionalAttributeValueAsText(attr.key)"
                            @update:model-value="(value: string) => updateAdditionalAttribute(attr.key, value)"
                            class="w-full"
                            :placeholder="attr.placeholder"
                        />
                    </template>
                    <template v-else-if="attr.type === 'timestamp'">
                        <Calendar
                            :model-value="getAdditionalAttributeValueAsDate(attr.key)"
                            @update:model-value="(value: Date | null) => updateAdditionalAttribute(attr.key, value)"
                            class="w-full"
                            :showTime="attr.showTime"
                            :dateFormat="attr.dateFormat"
                        />
                    </template>
                    <template v-else-if="attr.type === 'multiselect'">
                        <MultiSelect
                            :model-value="getAdditionalAttributeValue(attr.key, attr.type)"
                            @update:model-value="(value: string[]) => updateAdditionalAttribute(attr.key, value)"
                            :options="attr.options"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        />
                    </template>
                    <template v-else-if="attr.type === 'array'">
                        <div class="flex flex-wrap gap-2">
                            <InputNumber
                                v-for="(item, index) in getAdditionalAttributeValueAsArray(attr.key)"
                                :key="index"
                                :model-value="item"
                                @update:model-value="(value: number) => {
                                    const arrayValue = [...getAdditionalAttributeValueAsArray(attr.key)];
                                    arrayValue[index] = value;
                                    updateAdditionalAttribute(attr.key, arrayValue);
                                }"
                                showButtons
                                buttonLayout="horizontal"
                                class="w-full"
                                :min="getArrayConstraint(attr.key, index, 'min')"
                                :max="getArrayConstraint(attr.key, index, 'max')"
                                :placeholder="index === 0 ? 'Szansa % (1-99)' : 'Wartość'"
                            />
                        </div>
                    </template>
                </div>
            </div>
        </section>
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
