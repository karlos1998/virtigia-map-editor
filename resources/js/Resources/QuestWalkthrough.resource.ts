export interface WalkthroughLocation {
    map_id: number;
    map_name: string;
    x: number;
    y: number;
}

export interface WalkthroughNpc {
    base_npc_id: number | null;
    name: string;
    src: string | null;
    auto_start_dialog?: boolean;
    lvl?: number | null;
    locations: WalkthroughLocation[];
    locations_total: number;
    battle_dialogs?: { dialog_id: number; auto_start: boolean; timeline: WalkthroughTimelineEntry[] }[];
}

export interface WalkthroughRequirement {
    type: string;
    text: string;
    value?: unknown;
    item_id?: number;
    quantity?: number;
}

export interface WalkthroughStepRef {
    id: number;
    name: string;
    description: string | null;
    visible_in_quest_list: boolean;
}

export type WalkthroughTimelineEntry = { type: string; [key: string]: any } & {
    label?: string;
    npc_text?: string | null;
    requirements?: WalkthroughRequirement[];
    step?: WalkthroughStepRef | null;
};

export interface WalkthroughDialogSection {
    type: "dialog";
    purpose?: "item";
    item_id?: number;
    dialog_id: number;
    dialog_name: string | null;
    npcs: WalkthroughNpc[];
    timeline: WalkthroughTimelineEntry[];
    ends_with: string;
    assumes_other_quests: boolean;
    reached_steps: WalkthroughStepRef[];
    step?: WalkthroughStepRef | null;
}

export interface WalkthroughWaitSection {
    type: "wait";
    step_id: number;
    step?: WalkthroughStepRef | null;
    seconds: number;
}

export interface WalkthroughNextDaySection {
    type: "next_day";
    step_id: number;
    step?: WalkthroughStepRef | null;
    resets_quest: boolean;
}

export interface WalkthroughKillSection {
    type: "kill";
    step_id: number;
    step?: WalkthroughStepRef | null;
    targets: {
        quantity: number;
        base_npc_id: number | null;
        mob_species_id: number | null;
        name: string;
        npcs: WalkthroughNpc[];
    }[];
}

export type WalkthroughSection = (WalkthroughDialogSection | WalkthroughWaitSection | WalkthroughNextDaySection | WalkthroughKillSection) & {
    purpose?: string;
    item_id?: number;
};

export interface WalkthroughItemReference {
    id: number;
    name: string;
    src: string | null;
    sources: any[];
    sources_total: number;
}

export interface WalkthroughResource {
    quest: { id: number; name: string };
    status: "complete" | "partial" | "empty";
    finished: boolean;
    summary: {
        required_level: number | null;
        gold_cost: number;
        honor_cost: number;
        dragon_tears_cost: number;
        wait_seconds: number;
        next_day_waits: number;
        conversations: number;
        fights: number;
        rewards: {
            exp: number;
            exp_percent: number;
            gold: number;
            honor_points: number;
            items: { item_id: number; quantity: number }[];
            blessings: number[];
        };
        required_items: { item_id: number; quantity: number }[];
    };
    sections: WalkthroughSection[];
    warnings: string[];
    references: { items: Record<string, WalkthroughItemReference> | [] };
    generator_version: number;
}
