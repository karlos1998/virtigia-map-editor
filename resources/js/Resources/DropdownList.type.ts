export type DropdownListType<V = string, A extends Record<string, any> = {}> = ({
    label: string;
    value: V;
} & A)[];
