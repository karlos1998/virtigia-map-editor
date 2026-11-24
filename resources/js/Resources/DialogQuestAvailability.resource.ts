export interface DialogQuestAvailability {
    status: 'pending' | 'uncompiled' | 'ready';
    reason: string | null;
    quests: {
        id: number;
        name: string | null;
        paths: QuestMarkerCondition[][];
    }[];
}

export interface QuestMarkerCondition {
    profession?: string;
    optionId?: number;
    check: QuestMarkerRuleCheck;
    unless?: QuestMarkerRuleCheck[];
}

export interface QuestMarkerRuleCheck {
    optionEdge?: boolean;
    rules: Record<string, { value?: unknown; value2?: unknown }>;
}
