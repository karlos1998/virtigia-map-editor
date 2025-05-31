import { DialogNodeOptionRule } from "@/types/DialogNodeOptionRule";

export type DialogNodeRulesResource = Partial<
    Record<
        DialogNodeOptionRule,
        { value: number | number[] | string; consume: boolean, value2: number | number[] | undefined | null }
    >
>;
