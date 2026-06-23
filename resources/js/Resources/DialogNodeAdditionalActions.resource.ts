import {DialogNodeAdditionalAction} from "@/types/DialogNodeAdditionalAction";

export type DialogNodeAdditionalActionsResource = Partial<Record<DialogNodeAdditionalAction, {
    value: string | null;
    duration?: number;
} | {
    value: number | number[] | null;
    value2?: number[];
    duration?: number;
    scale?: boolean;
}>>
