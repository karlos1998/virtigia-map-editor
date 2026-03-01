import { BaseNpcResource } from './BaseNpc.resource';

export interface SimpleDialogResource {
    id: number;
    name: string;
}

export interface SimpleDialogNodeResource {
    id: number;
    content: string;
    type: string;
}

export interface SimpleDialogNodeOptionResource {
    id: number;
    label: string;
}

export interface SimpleDialogEdgeResource {
    id: number;
    rules: any;
    source_dialog_id: number;
    source_node_id: number | null;
    source_option_id: number | null;
    target_node_id: number;
}

export interface QuestResource {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface SimpleQuestResource {
    id: number;
    name: string;
    is_daily: boolean;
}

export interface QuestWithStepsResource extends QuestResource {
    steps: QuestStepResource[];
    dialogs: SimpleDialogResource[];
    nodes: SimpleDialogNodeResource[];
    nodeOptions: SimpleDialogNodeOptionResource[];
    edges: SimpleDialogEdgeResource[];
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
    visible_in_quest_list: boolean;
    auto_progress?: QuestStepAutoProgress | null;
    dialogs: SimpleDialogResource[];
    nodes: SimpleDialogNodeResource[];
    nodeOptions: SimpleDialogNodeOptionResource[];
    edges: SimpleDialogEdgeResource[];
    created_at: string;
    updated_at: string;
}
