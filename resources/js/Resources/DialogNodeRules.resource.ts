import { DialogNodeOptionRule } from "@/types/DialogNodeOptionRule";

export type DialogNodeRulesResource = Partial<
    Record<
        DialogNodeOptionRule,
        { value: number | number[]; consume: boolean, value2: number | number[] | undefined | null }
    >
>;
