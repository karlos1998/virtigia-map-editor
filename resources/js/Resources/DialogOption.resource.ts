import { DialogRuleResource } from './DialogRule.resource';
import { DialogConnectionResource } from './DialogConnection.resource';
import {DialogNodeOptionRule} from "../types/DialogNodeOptionRule";

export interface DialogOptionResource {
    id: number
    label: string
    node_id: number
    additional_action: string|null; //todo ? moze enum
    rules: Record<DialogNodeOptionRule, { value: number| number[]; consume: boolean }>
}
