import {DialogNodeRulesResource} from "./DialogNodeRules.resource";

export interface DialogOptionResource {
    id: number
    label: string
    node_id: number
    additional_action: string|null; //todo ? moze enum
    rules: DialogNodeRulesResource
    edges: DialogNodeOptionEdgeWithRules[]
}

export interface  DialogNodeOptionEdgeWithRules {
    edge_id: number
    node: {
        id: number
        type: string
        content: string
    }
    rules?: DialogNodeRulesResource
}
