export interface QuestResource {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface QuestWithStepsResource extends QuestResource {
    steps: QuestStepResource[];
}

export interface QuestStepResource {
    id: number;
    quest_id: number;
    name: string;
    description: string;
    created_at: string;
    updated_at: string;
}
