import { DialogRuleResource } from './DialogRule.resource';
import { DialogConnectionResource } from './DialogConnection.resource';

export interface DialogOptionResource {
    id?: number | null;
    dialogId?: number | null;
    content: string;
    rules?: DialogRuleResource[];
    targetDialogs: DialogConnectionResource[];
}
