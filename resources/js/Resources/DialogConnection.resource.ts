import { DialogRuleResource } from './DialogRule.resource';

export interface DialogConnectionResource {
    sourceOptionId?: number | null;
    sourceGroupId?: number | null;
    targetDialogId: number;
    rules?: DialogRuleResource[];
}
