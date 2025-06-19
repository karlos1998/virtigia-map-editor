import { BaseNpcResource } from './BaseNpc.resource';

export interface QuestResource {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface QuestWithStepsResource extends QuestResource {
    steps: QuestStepResource[];
}

export interface QuestStepAutoProgressMob {
    base_npc_id: number;
    quantity: number;
    base_npc?: BaseNpcResource;
}

export interface QuestStepAutoProgress {
    type: 'time' | 'mobs';
    time_seconds?: number;
    mobs?: QuestStepAutoProgressMob[];
}

export interface QuestStepResource {
    id: number;
    quest_id: number;
    name: string;
    description: string;
    auto_progress?: QuestStepAutoProgress | null;
    created_at: string;
    updated_at: string;
}
