import { DialogNodeOptionRule } from "@/types/DialogNodeOptionRule";

export type DialogNodeRuleValue = number | number[] | string | string[] | boolean | null;

export type DialogNodeRuleValue2 = number | number[] | string | Array<number | string> | null | undefined;

export type DialogNodeRuleData = {
    value: DialogNodeRuleValue;
    consume: boolean;
    value2: DialogNodeRuleValue2;
}

export type DialogNodeRulesResource = Partial<
    Record<
        DialogNodeOptionRule,
        DialogNodeRuleData
    >
>;
