import { DialogNodeOptionRule } from "@/types/DialogNodeOptionRule";

export type MessageContentRuleType = {
    value: string; // max 100 znaków
    consume: boolean; // tak jak w pozostałych (dla spójności)
}

export type DialogNodeRulesResource = Partial<
    Record<
        DialogNodeOptionRule,
        DialogNodeOptionRule extends 'messageContent'
            ? MessageContentRuleType
            : { value: number | number[] | string; consume: boolean, value2: number | number[] | undefined | null }
    >
>;
