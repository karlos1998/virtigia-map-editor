export type DialogNodeFocusType = 'npc' | 'coordinates' | 'reset';

export interface DialogNodeFocusResource {
    type: DialogNodeFocusType;
    npcId?: number | null;
    locationId?: number | null;
    mapId?: number | null;
    x?: number | null;
    y?: number | null;
}

export type DialogAnswerTimeoutAction = 'DEATH' | 'RANDOM_OPTION' | 'SELECT_OPTION' | 'CLOSE_DIALOG';

export interface DialogNodeEffectsResource {
    shake?: {durationMs: number; intensity: number};
    dim?: {durationMs: number; opacity: number; holdMs?: number | null};
    sound?: {audioId: number};
}

export interface DialogNodeActionDataResource {
    answerTimeout?: {seconds: number; action: DialogAnswerTimeoutAction; optionId?: number | null} | null;
    focus?: DialogNodeFocusResource | null;
    effects?: DialogNodeEffectsResource | null;
    minigame?: {
        type?: string;
        difficulty?: number;
    };
    teleportation?: Record<string, unknown>;
}
