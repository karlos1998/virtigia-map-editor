import { DialogRuleResource } from './DialogRule.resource';
import { DialogConnectionResource } from './DialogConnection.resource';

export interface DialogOptionResource {
    id: number
    label: string
    node_id: number
    additional_action: string|null; //todo ? moze enum
}
