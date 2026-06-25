import {
    RAW_ATTRIBUTE_TO_ATTACK_ELEMENT,
    RAW_ATTRIBUTE_TO_ATTRIBUTE_POINT,
    RAW_ATTRIBUTE_TO_MANUAL_ATTRIBUTE_POINT,
} from './AttributeCategoryRules';

type PriceCalculatorInput = {
    category?: string | null;
    rarity?: string | null;
    currency?: string | null;
    attributes?: Record<string, any> | null;
    attributePoints?: Record<string, any> | null;
    manualAttributePoints?: Record<string, any> | null;
};

const RARITY_FACTOR: Record<string, number> = {
    common: 0,
    unique: 1,
    heroic: 2,
    upgraded: 2,
    legendary: 3,
    artefact: 5,
};

// Fitted from stored base_items prices in retro-20260623-224024.sql against the backend level/rarity scaler.
const CATEGORY_MULTIPLIER: Record<string, number> = {
    oneHanded: 3.4112,
    armors: 2.4979,
    twoHanded: 2.8065,
    halfHanded: 4.0842,
    gloves: 1.4677,
    helmets: 1.4895,
    boots: 1.5699,
    rings: 1.6383,
    necklaces: 1.7382,
    shields: 2.0787,
    staffs: 3.1825,
    auxiliary: 2.8475,
    quests: 0.0611,
    consumable: 0.4728,
    neutrals: 3.1966,
    backpacks: 1.6827,
    wands: 4.6005,
    distances: 4.2741,
    arrows: 0.0013,
    talismans: 0.7216,
    upgrades: 0.0153,
    books: 38.1679,
    musicBoxes: 38.1679,
    keys: 0.0687,
    golds: 47.4666,
    blessings: 0.1457,
    pets: 0.0104,
    pouches: 1.0356,
};

const RARITY_MULTIPLIER: Record<string, number> = {
    common: 0.92,
    unique: 1.09,
    heroic: 1.52,
    upgraded: 0.01,
    legendary: 2.17,
    artefact: 0.48,
};

const CURRENCY_MULTIPLIER: Record<string, number> = {
    gold: 1,
    dragonTear: 0.006,
    honor: 0.02,
    unset: 0.01,
};

const PROFESSION_COUNT_MULTIPLIER: Record<number, number> = {
    0: 0.95,
    1: 1,
    2: 1.08,
    3: 1.12,
    4: 1.35,
    5: 1.4,
    6: 1.45,
};

const BONUS_COUNT_MULTIPLIER: Record<number, number> = {
    0: 0.9,
    1: 0.95,
    2: 1,
    3: 1.08,
    4: 1.18,
    5: 1.28,
    6: 1.4,
    7: 1.52,
    8: 1.64,
    9: 1.78,
    10: 1.92,
    11: 2.1,
    12: 2.25,
};

const ATTACK_ELEMENT_MULTIPLIER: Record<string, number> = {
    PHYSICAL: 1,
    FIRE: 1.06,
    FROST: 1.18,
    LIGHT: 1.04,
    POISON: 1.05,
};

const ATTRIBUTE_POINT_WEIGHT: Record<string, number> = {
    armor: 0.03,
    criticalChance: 0.05,
    allBaseAttributes: 0.06,
    attackSpeed: 0.06,
    armorDest: 0.08,
    absorption: 0.04,
    absDest: 0.06,
    fireResist: 0.03,
    frostResist: 0.03,
    lightResist: 0.03,
    poisonResist: 0.03,
    resistDest: 0.08,
    critReduction: 0.04,
    physicalCriticStrength: 0.06,
    magicalCriticStrength: 0.06,
    strength: 0.05,
    agility: 0.05,
    intellect: 0.05,
    energy: 0.04,
    energyDest: 0.05,
    mana: 0.04,
    manaDest: 0.05,
    health: 0.04,
    healing: 0.05,
    evasion: 0.06,
    evasionReduction: 0.05,
    block: 0.05,
    attackSpeedReduction: 0.04,
};

const MANUAL_ATTRIBUTE_WEIGHT: Record<string, number> = {
    counter: 0.08,
    pierce: 0.12,
    pierceBlock: 0.08,
    deepWoundChance: 0.1,
};

function calculateR(level: number, rarity: string | null | undefined): number {
    const rarityFactor = RARITY_FACTOR[rarity || 'common'] ?? 0;

    return level * level
        + (130 + Math.ceil(10 / 3 * rarityFactor)) * level
        + (130 + 390 * rarityFactor) * Math.sign(rarityFactor);
}

function toPositiveNumber(value: unknown): number {
    const numericValue = typeof value === 'number' ? value : Number(value);
    return Number.isFinite(numericValue) ? Math.max(0, numericValue) : 0;
}

function hasValue(value: unknown): boolean {
    if (value === null || value === undefined) {
        return false;
    }

    if (typeof value === 'number') {
        return value !== 0;
    }

    if (typeof value === 'string') {
        return value.trim() !== '';
    }

    if (Array.isArray(value)) {
        return value.some(hasValue);
    }

    if (typeof value === 'object') {
        return Object.values(value).some(hasValue);
    }

    return Boolean(value);
}

function mergePointMap(target: Record<string, number>, source: unknown): void {
    if (Array.isArray(source)) {
        source.forEach(key => {
            if (typeof key === 'string') {
                target[key] = Math.max(target[key] ?? 0, 1);
            }
        });
        return;
    }

    if (!source || typeof source !== 'object') {
        return;
    }

    Object.entries(source as Record<string, unknown>).forEach(([key, value]) => {
        const numericValue = toPositiveNumber(value);
        if (numericValue > 0) {
            target[key] = (target[key] ?? 0) + numericValue;
        }
    });
}

function extractPriceData(input: PriceCalculatorInput) {
    const attributes = input.attributes ?? {};
    const attributePoints: Record<string, number> = {};
    const manualAttributePoints: Record<string, number> = {};
    const attackElements = new Set<string>();

    mergePointMap(attributePoints, input.attributePoints);
    mergePointMap(manualAttributePoints, input.manualAttributePoints);

    const selectedAttackElements = attributes.needAttackElements;
    if (Array.isArray(selectedAttackElements)) {
        selectedAttackElements.forEach(attackElement => {
            if (typeof attackElement === 'string') {
                attackElements.add(attackElement);
            }
        });
    }

    Object.entries(RAW_ATTRIBUTE_TO_ATTRIBUTE_POINT).forEach(([attributeKey, attributePointKey]) => {
        if (hasValue(attributes[attributeKey]) && !attributePoints[attributePointKey]) {
            attributePoints[attributePointKey] = 1;
        }
    });

    Object.entries(RAW_ATTRIBUTE_TO_MANUAL_ATTRIBUTE_POINT).forEach(([attributeKey, manualAttributePointKey]) => {
        if (hasValue(attributes[attributeKey]) && !manualAttributePoints[manualAttributePointKey]) {
            manualAttributePoints[manualAttributePointKey] = 1;
        }
    });

    Object.entries(RAW_ATTRIBUTE_TO_ATTACK_ELEMENT).forEach(([attributeKey, attackElement]) => {
        if (hasValue(attributes[attributeKey])) {
            attackElements.add(attackElement);
        }
    });

    return {attributePoints, manualAttributePoints, attackElements};
}

function getNeedLevel(input: PriceCalculatorInput): number {
    const attributeLevel = toPositiveNumber(input.attributes?.needLevel);

    return Math.max(1, Math.round(attributeLevel || 1));
}

function getNeedProfessions(input: PriceCalculatorInput): string[] {
    const professions = new Set<string>();

    if (Array.isArray(input.attributes?.needProfessions)) {
        input.attributes.needProfessions.forEach(value => {
            if (typeof value === 'string') {
                professions.add(value);
            }
        });
    }

    return [...professions];
}

function getBonusCount(attributePoints: Record<string, number>, manualAttributePoints: Record<string, number>, attackElements: Set<string>): number {
    const attributePointCount = Object.values(attributePoints).filter(value => value !== 0).length;
    const manualAttributePointCount = Object.values(manualAttributePoints).filter(value => value !== 0).length;
    const attackElementCount = [...attackElements].filter(attackElement => attackElement !== 'DEEP_WOUND').length;

    return Math.min(12, attributePointCount + manualAttributePointCount + attackElementCount);
}

function getAttributeQualityMultiplier(attributePoints: Record<string, number>, manualAttributePoints: Record<string, number>): number {
    const pointScore = Object.entries(attributePoints).reduce((sum, [key, value]) => {
        return sum + Math.log1p(Math.abs(value)) * (ATTRIBUTE_POINT_WEIGHT[key] ?? 0.04);
    }, 0);
    const manualScore = Object.entries(manualAttributePoints).reduce((sum, [key, value]) => {
        return sum + Math.log1p(Math.abs(value)) * (MANUAL_ATTRIBUTE_WEIGHT[key] ?? 0.08);
    }, 0);

    return Math.min(2.2, 1 + pointScore + manualScore);
}

function getAttackMultiplier(attackElements: Set<string>): number {
    return [...attackElements].reduce((multiplier, attackElement) => {
        return multiplier * (ATTACK_ELEMENT_MULTIPLIER[attackElement] ?? 1);
    }, 1);
}

function roundPrice(price: number): number {
    if (price < 100) {
        return Math.max(1, Math.round(price));
    }
    if (price < 1000) {
        return Math.round(price / 10) * 10;
    }
    if (price < 10000) {
        return Math.round(price / 100) * 100;
    }

    return Math.round(price / 1000) * 1000;
}

export function calculateBaseItemPrice(input: PriceCalculatorInput): number {
    const level = getNeedLevel(input);
    const rarity = input.rarity || 'common';
    const category = input.category || 'neutrals';
    const currency = input.currency || 'gold';
    const professions = getNeedProfessions(input);
    const {attributePoints, manualAttributePoints, attackElements} = extractPriceData(input);
    const bonusCount = getBonusCount(attributePoints, manualAttributePoints, attackElements);

    const rawPrice = calculateR(level, rarity)
        * (CATEGORY_MULTIPLIER[category] ?? CATEGORY_MULTIPLIER.neutrals)
        * (RARITY_MULTIPLIER[rarity] ?? RARITY_MULTIPLIER.common)
        * (CURRENCY_MULTIPLIER[currency] ?? CURRENCY_MULTIPLIER.gold)
        * (PROFESSION_COUNT_MULTIPLIER[Math.min(6, professions.length)] ?? 1)
        * (BONUS_COUNT_MULTIPLIER[bonusCount] ?? BONUS_COUNT_MULTIPLIER[12])
        * getAttributeQualityMultiplier(attributePoints, manualAttributePoints)
        * getAttackMultiplier(attackElements);

    return Math.min(1_000_000_000, Math.max(0, roundPrice(rawPrice)));
}
