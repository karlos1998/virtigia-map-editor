export type BaseItemCategory =
    | 'oneHanded'
    | 'armors'
    | 'twoHanded'
    | 'halfHanded'
    | 'gloves'
    | 'helmets'
    | 'boots'
    | 'rings'
    | 'necklaces'
    | 'shields'
    | 'staffs'
    | 'auxiliary'
    | 'quests'
    | 'consumable'
    | 'neutrals'
    | 'backpacks'
    | 'wands'
    | 'distances'
    | 'arrows'
    | 'talismans'
    | 'upgrades'
    | 'books'
    | 'musicBoxes'
    | 'keys'
    | 'golds'
    | 'blessings'
    | 'pets'
    | 'pouches';

export type AttributeCategoryGroup =
    | 'attributePoint'
    | 'manualAttributePoint'
    | 'booleanAttribute'
    | 'additionalAttribute'
    | 'attackElement';

const ALL_CATEGORIES: BaseItemCategory[] = [
    'oneHanded',
    'armors',
    'twoHanded',
    'halfHanded',
    'gloves',
    'helmets',
    'boots',
    'rings',
    'necklaces',
    'shields',
    'staffs',
    'auxiliary',
    'quests',
    'consumable',
    'neutrals',
    'backpacks',
    'wands',
    'distances',
    'arrows',
    'talismans',
    'upgrades',
    'books',
    'musicBoxes',
    'keys',
    'golds',
    'blessings',
    'pets',
    'pouches',
];

const ARMOR_PARTS = ['armors', 'helmets', 'gloves', 'boots', 'shields'] as const;
const ARMOR_AND_JEWELRY = [...ARMOR_PARTS, 'rings', 'necklaces'] as const;
const PHYSICAL_WEAPONS = ['oneHanded', 'twoHanded', 'halfHanded', 'distances', 'arrows', 'auxiliary'] as const;
const MAGICAL_WEAPONS = ['staffs', 'wands', 'auxiliary'] as const;
const OFFENSIVE_ITEMS = [...PHYSICAL_WEAPONS, ...MAGICAL_WEAPONS] as const;
const COMBAT_ITEMS = [...OFFENSIVE_ITEMS, ...ARMOR_AND_JEWELRY, 'blessings'] as const;
const STACKABLE_ITEMS = [
    'arrows',
    'blessings',
    'consumable',
    'distances',
    'gloves',
    'halfHanded',
    'helmets',
    'neutrals',
    'oneHanded',
    'quests',
    'staffs',
    'twoHanded',
    'upgrades',
    'wands',
] as const;
const GENERIC_FLAG_ITEMS = [
    ...COMBAT_ITEMS,
    'backpacks',
    'books',
    'consumable',
    'keys',
    'neutrals',
    'pets',
    'pouches',
    'quests',
    'talismans',
    'upgrades',
] as const;

const unique = <T extends string>(values: readonly T[]): T[] => [...new Set(values)];

const ATTRIBUTE_POINT_CATEGORIES: Record<string, readonly BaseItemCategory[]> = {
    armor: [...ARMOR_PARTS, 'blessings'],
    criticalChance: COMBAT_ITEMS,
    allBaseAttributes: COMBAT_ITEMS,
    attackSpeed: COMBAT_ITEMS,
    armorDest: unique([...OFFENSIVE_ITEMS, ...ARMOR_AND_JEWELRY, 'blessings']),
    absorption: ARMOR_PARTS,
    absDest: OFFENSIVE_ITEMS,
    fireResist: [...ARMOR_AND_JEWELRY, 'blessings'],
    frostResist: [...ARMOR_AND_JEWELRY, 'blessings'],
    lightResist: [...ARMOR_AND_JEWELRY, 'blessings'],
    poisonResist: [...ARMOR_AND_JEWELRY, 'blessings'],
    resistDest: unique([...OFFENSIVE_ITEMS, 'rings', 'necklaces', 'helmets', 'gloves', 'boots']),
    critReduction: ARMOR_AND_JEWELRY,
    physicalCriticStrength: unique([...PHYSICAL_WEAPONS, ...ARMOR_AND_JEWELRY, 'blessings']),
    magicalCriticStrength: COMBAT_ITEMS,
    strength: unique(['oneHanded', 'twoHanded', 'halfHanded', 'auxiliary', ...ARMOR_AND_JEWELRY]),
    agility: unique(['oneHanded', 'distances', 'arrows', 'auxiliary', 'armors', 'helmets', 'gloves', 'boots', 'rings', 'necklaces']),
    intellect: unique([...MAGICAL_WEAPONS, 'armors', 'helmets', 'gloves', 'boots', 'rings', 'necklaces']),
    energy: unique([...PHYSICAL_WEAPONS, ...ARMOR_AND_JEWELRY]),
    energyDest: unique(['oneHanded', 'distances', 'auxiliary', 'staffs', 'wands', 'helmets', 'gloves', 'boots', 'rings', 'necklaces']),
    mana: COMBAT_ITEMS,
    manaDest: unique(['arrows', 'distances', 'auxiliary', 'staffs', 'wands', 'helmets', 'gloves', 'boots', 'rings', 'necklaces', 'shields']),
    health: COMBAT_ITEMS,
    healing: COMBAT_ITEMS,
    evasion: COMBAT_ITEMS,
    evasionReduction: COMBAT_ITEMS,
    block: [...ARMOR_AND_JEWELRY, 'blessings'],
    attackSpeedReduction: COMBAT_ITEMS,
};

const MANUAL_ATTRIBUTE_POINT_CATEGORIES: Record<string, readonly BaseItemCategory[]> = {
    counter: ['oneHanded'],
    pierce: ['distances'],
    pierceBlock: ['shields'],
    deepWoundChance: ['oneHanded', 'twoHanded', 'halfHanded', 'distances', 'auxiliary'],
};

const BOOLEAN_ATTRIBUTE_CATEGORIES: Record<string, readonly BaseItemCategory[]> = {
    isNonStoreableInClanDeposit: GENERIC_FLAG_ITEMS,
    isBindPermanentlyAfterBuy: ['blessings', 'consumable'],
    isNonStoreableInDeposit: GENERIC_FLAG_ITEMS,
    isPermanentlyBounded: GENERIC_FLAG_ITEMS,
    isBindsAfterEquip: GENERIC_FLAG_ITEMS,
    isNotAuctionable: GENERIC_FLAG_ITEMS,
    isBoundToOwner: GENERIC_FLAG_ITEMS,
    unbindsOwnerBound: ['upgrades'],
    unbindsPermanentlyBound: ['upgrades'],
    isRecovered: COMBAT_ITEMS,
    isUnidentified: unique([...COMBAT_ITEMS, 'consumable', 'keys', 'neutrals', 'quests', 'talismans']),
    findHeroNpc: ['consumable'],
    findDetailedHeroNpc: ['consumable'],
    combatFlee: ['consumable'],
    openDeposit: ['consumable'],
    openClanDeposit: ['consumable'],
    openMail: ['consumable'],
    openAuction: ['consumable'],
    impossibleToRemove: ['blessings'],
};

const ADDITIONAL_ATTRIBUTE_CATEGORIES: Record<string, readonly BaseItemCategory[]> = {
    shortenRevival: ['consumable'],
    description: ALL_CATEGORIES,
    quantity: STACKABLE_ITEMS,
    incrementGold: ['golds', 'consumable'],
    healRemaining: ['consumable'],
    maxQuantity: STACKABLE_ITEMS,
    expiresOn: ['blessings', 'consumable', 'talismans'],
    healthRestorationPercent: ['consumable'],
    bagCapacity: ['backpacks', 'pouches'],
    restoreHealthPoints: ['consumable'],
    stamina: ['consumable'],
    addDraconite: ['consumable'],
    legendaryLootChanceBonusPercent: ['blessings'],
    heroicLootChanceBonusPercent: ['blessings'],
    minimumLootChancePercent: ['blessings'],
    battleExperienceBonusPercent: ['blessings'],
    questExperienceBonusPercent: ['blessings'],
    arrowPreservationChancePercent: ['blessings'],
    fasterRevivalRecovery: ['talismans'],
    timeToDisappear: ['blessings'],
    percentageUpgradeCommon: ['upgrades'],
    percentageUpgradeUnique: ['upgrades'],
    percentageUpgradeHeroic: ['upgrades'],
    percentageUpgradeLegendary: ['upgrades'],
    upgradeableCategories: ['upgrades'],
    storableCategories: ['pouches'],
    reduceLevelRequirementCommon: ['upgrades'],
    reduceLevelRequirementUnique: ['upgrades'],
    reduceLevelRequirementHeroic: ['upgrades'],
    reduceLevelRequirementLegendary: ['upgrades'],
    healChanceAfterFight: ['talismans'],
};

const ATTACK_ELEMENT_CATEGORIES: Record<string, readonly BaseItemCategory[]> = {
    PHYSICAL: PHYSICAL_WEAPONS,
    FIRE: unique([...OFFENSIVE_ITEMS, 'halfHanded']),
    FROST: unique([...OFFENSIVE_ITEMS, 'halfHanded']),
    LIGHT: unique([...OFFENSIVE_ITEMS, 'halfHanded']),
    POISON: PHYSICAL_WEAPONS,
    DEEP_WOUND: ['oneHanded', 'twoHanded', 'halfHanded', 'distances', 'auxiliary'],
};

const CATEGORY_RULES: Record<AttributeCategoryGroup, Record<string, readonly BaseItemCategory[]>> = {
    attributePoint: ATTRIBUTE_POINT_CATEGORIES,
    manualAttributePoint: MANUAL_ATTRIBUTE_POINT_CATEGORIES,
    booleanAttribute: BOOLEAN_ATTRIBUTE_CATEGORIES,
    additionalAttribute: ADDITIONAL_ATTRIBUTE_CATEGORIES,
    attackElement: ATTACK_ELEMENT_CATEGORIES,
};

export const RAW_ATTRIBUTE_TO_ATTRIBUTE_POINT: Record<string, string> = {
    defense: 'armor',
    criticalChance: 'criticalChance',
    allBaseAttributes: 'allBaseAttributes',
    attackSpeed: 'attackSpeed',
    defenseDestroy: 'armorDest',
    physicalDamageAbsorption: 'absorption',
    magicalDamageAbsorption: 'absorption',
    absorptionDestroy: 'absDest',
    fireResistance: 'fireResist',
    frostResistance: 'frostResist',
    lightResistance: 'lightResist',
    poisonResistance: 'poisonResist',
    magicalResistanceReduction: 'resistDest',
    criticalReductionDuringDefending: 'critReduction',
    physicalCritPower: 'physicalCriticStrength',
    magicalCritPower: 'magicalCriticStrength',
    strength: 'strength',
    agility: 'agility',
    intellect: 'intellect',
    energy: 'energy',
    energyDestroy: 'energyDest',
    mana: 'mana',
    enemyManaReduction: 'manaDest',
    health: 'health',
    combatHealthRestoration: 'healing',
    evadePoints: 'evasion',
    enemyEvasionReduction: 'evasionReduction',
    blockPoints: 'block',
    enemyAttackSpeedReduction: 'attackSpeedReduction',
};

export const RAW_ATTRIBUTE_TO_MANUAL_ATTRIBUTE_POINT: Record<string, string> = {
    chanceToCounter: 'counter',
    armorPuncture: 'pierce',
    chanceToBlockPuncture: 'pierceBlock',
    deepWoundChance: 'deepWoundChance',
};

export const RAW_ATTRIBUTE_TO_ATTACK_ELEMENT: Record<string, string> = {
    physicalDamage: 'PHYSICAL',
    arrowPhysicalDamage: 'PHYSICAL',
    fireDamage: 'FIRE',
    frostDamage: 'FROST',
    lightDamage: 'LIGHT',
    poisonDamage: 'POISON',
};

export function isKnownBaseItemCategory(category: string | null | undefined): category is BaseItemCategory {
    return ALL_CATEGORIES.includes(category as BaseItemCategory);
}

export function isAttributeAllowedForCategory(
    group: AttributeCategoryGroup,
    key: string,
    category: string | null | undefined,
): boolean {
    if (!isKnownBaseItemCategory(category)) {
        return true;
    }

    const allowedCategories = CATEGORY_RULES[group][key];
    if (!allowedCategories) {
        return true;
    }

    return allowedCategories.includes(category);
}
