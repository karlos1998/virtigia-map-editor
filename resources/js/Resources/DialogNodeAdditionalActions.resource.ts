import {DialogNodeAdditionalAction} from "@/types/DialogNodeAdditionalAction";

export type DialogNodeAdditionalActionsResource = Partial<Record<DialogNodeAdditionalAction, {
    value: string;
    duration?: number;
} | {
    value: number | number[];
    duration?: number;
    scale?: boolean;
}>>
