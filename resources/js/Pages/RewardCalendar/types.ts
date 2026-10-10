export type RewardCalendarStyle = 'blue' | 'red' | 'orange';

export type RewardCalendarStyleOption = {
    value: RewardCalendarStyle;
    label: string;
};

export type RewardCalendarListItem = {
    id: number;
    name: string;
    year: number | null;
    is_recurring: boolean;
    style: RewardCalendarStyle;
    style_label: string;
    enabled: boolean;
    days_count: number;
    special_days_count: number;
    starts_on: string | null;
    ends_on: string | null;
    is_active_now: boolean;
};

/** Konfiguracja nagród w formacie kuferka (zgodna z LootChestEditor). */
export type RewardConfig = {
    mode: 'random' | 'guaranteed';
    rewardBinding: 'none' | 'boundToOwner' | 'permanentlyBounded' | 'bindsAfterEquip';
    minRewards: number;
    maxRewards: number;
    items: Array<{
        baseItemId: number;
        chancePercent: number;
        minQuantity: number;
        maxQuantity: number;
    }>;
};

export type RewardCalendarTier = {
    minOpenedDays: number;
    rewards: RewardConfig;
};

export type RewardCalendarDay = {
    id: number;
    month: number;
    day: number;
    type: 'normal' | 'special';
    rewards: RewardConfig | null;
    tiers: RewardCalendarTier[] | null;
};

export type RewardCalendarBaseItemPreview = {
    id: number;
    name: string;
    src: string;
};

export const MONTH_NAMES = [
    'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
    'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień',
];

export const formatCalendarDate = (day: number, month: number): string => `${day} ${MONTH_NAMES[month - 1]?.toLowerCase() ?? month}`;
