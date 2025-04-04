import { DialogNodeOptionRule } from "@/types/DialogNodeOptionRule";

export type DialogNodeRulesResource = Partial<
    Record<
        DialogNodeOptionRule,
        { value: number | number[]; consume: boolean }
    >
>;
