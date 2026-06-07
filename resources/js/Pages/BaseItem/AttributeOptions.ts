export interface BaseItemAttributeOption {
    key: string;
    label: string;
}

export interface BooleanAttribute extends BaseItemAttributeOption {}

export type AdditionalAttributeType = 'int' | 'decimal' | 'string' | 'timestamp' | 'text' | 'multiselect' | 'array';

export interface AdditionalAttribute extends BaseItemAttributeOption {
    type: AdditionalAttributeType;
    placeholder?: string;
    showTime?: boolean;
    dateFormat?: string;
    options?: Array<{ label: string; value: string }>;
    arraySize?: number;
    arrayConstraints?: Array<{ min?: number; max?: number }>;
}

export const booleanAttributes: BooleanAttribute[] = [
    {key: 'isNonStoreableInClanDeposit', label: 'Przedmiotu nie można przechowywać w depozycie klanowym'},
    {key: 'isBindPermanentlyAfterBuy', label: 'Wiąże na stałe po kupieniu'},
    {key: 'isNonStoreableInDeposit', label: 'Przedmiotu nie można przechowywać w depozycie'},
    {key: 'isPermanentlyBounded', label: 'Związany z właścicielem na stałe'},
    {key: 'isBindsAfterEquip', label: 'Wiąże po założeniu'},
    {key: 'isNotAuctionable', label: 'Tego przedmiotu nie można wystawiać na aukcję'},
    {key: 'isBoundToOwner', label: 'Związany z włascicielem'},
    {key: 'unbindsOwnerBound', label: 'Odwiązuje przedmiot związany z właścicielem'},
    {key: 'unbindsPermanentlyBound', label: 'Odwiązuje przedmiot związany na stałe (też zwykły bind)'},
    {key: 'isRecovered', label: 'Przedmiot odzyskany, obniżona wartość'},
    {key: 'isUnidentified', label: 'Przedmiot niezidentyfikowany'},
    {key: 'findHeroNpc', label: 'Wskazuje najbliższego herosa do ubicia'},
    {key: 'findDetailedHeroNpc', label: 'Wskazuje herosa z pozycją i czasem odrodzenia'},
    {key: 'combatFlee', label: 'Pozwala na ucieczkę z walki'},
    {key: 'openDeposit', label: 'Wywołuje depozyt'},
    {key: 'openClanDeposit', label: 'Wywołuje depozyt klanowy'},
    {key: 'openMail', label: 'Wywołuje pocztę'},
    {key: 'openAuction', label: 'Wywołuje aukcję'},
    {key: 'impossibleToRemove', label: 'Czar niemożliwy do zdjęcia'},
];

export const additionalAttributes: AdditionalAttribute[] = [
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
    {key: 'legendaryLootChanceBonusPercent', label: 'Zwiększa szansę na zdobycie przedmiotu legendarnego o %', type: 'decimal'},
    {key: 'heroicLootChanceBonusPercent', label: 'Zwiększa szansę na zdobycie przedmiotu heroicznego o %', type: 'decimal'},
    {key: 'minimumLootChancePercent', label: 'Zmniejsza szansę na pusty łup do %', type: 'int'},
    {key: 'battleExperienceBonusPercent', label: 'Zwiększa/zmniejsza doświadczenie za walkę o %', type: 'int'},
    {key: 'questExperienceBonusPercent', label: 'Zwiększa/zmniejsza doświadczenie za questy o %', type: 'int'},
    {key: 'arrowPreservationChancePercent', label: 'Szansa na zachowanie strzały podczas ataku (%)', type: 'int'},
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
            {label: 'Sakwy', value: 'pouches'},
        ],
    },
    {
        key: 'storableCategories', label: 'Pozwala przechowywać', type: 'multiselect', options: [
            {label: 'Questowe', value: 'quests'},
            {label: 'Konsumpcyjne', value: 'consumable'},
            {label: 'Neutralne', value: 'neutrals'},
            {label: 'Strzały', value: 'arrows'},
            {label: 'Talizmany', value: 'talismans'},
            {label: 'Ulepszenia', value: 'upgrades'},
            {label: 'Książki', value: 'books'},
            {label: 'Pozytywki', value: 'musicBoxes'},
            {label: 'Klucze', value: 'keys'},
            {label: 'Złoto', value: 'golds'},
            {label: 'Błogosławieństwa', value: 'blessings'},
            {label: 'Zwierzaki', value: 'pets'},
        ],
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
            {min: 1, max: 99},
            {},
        ],
    },
];
