import {DialogNodeRulesResource} from "./DialogNodeRules.resource";
import { DialogNodeAdditionalActionsResource } from "@/Resources/DialogNodeAdditionalActions.resource";

export interface DialogOptionResource {
    id: number
    label: string
    node_id: number
    additional_action: string|null; //todo ? moze enum
    additional_actions: DialogNodeAdditionalActionsResource|null;
    cooldown: number|null;
    rules: DialogNodeRulesResource
    edges: DialogNodeOptionEdgeWithRules[]
}

export interface  DialogNodeOptionEdgeWithRules {
    edge_id: number
    source_handle?: string|null
    node: {
        id: number
        type: string
        content: string
    }
    rules?: DialogNodeRulesResource
}
