import { DialogRuleEntityEnum } from '../Enums/DialogRuleEntityEnum';
import { DialogRuleOperatorEnum } from '../Enums/DialogRuleOperatorEnum';

export interface DialogRuleResource {
    entityType: DialogRuleEntityEnum;
    operator: DialogRuleOperatorEnum;
    value: string;
}
