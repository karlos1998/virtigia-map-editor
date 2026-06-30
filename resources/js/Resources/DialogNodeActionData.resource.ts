export type DialogNodeFocusType = 'npc' | 'coordinates' | 'reset';

export interface DialogNodeFocusResource {
    type: DialogNodeFocusType;
    npcId?: number | null;
    locationId?: number | null;
    mapId?: number | null;
    x?: number | null;
    y?: number | null;
}

export interface DialogNodeActionDataResource {
    focus?: DialogNodeFocusResource | null;
    minigame?: {
        type?: string;
        difficulty?: number;
    };
    teleportation?: Record<string, unknown>;
}
