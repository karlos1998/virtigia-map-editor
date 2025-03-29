import {DialogNodeOptionRule} from "../types/DialogNodeOptionRule";

export type DialogNodeRulesResource = Record<DialogNodeOptionRule, { value: number| number[]; consume: boolean }>
