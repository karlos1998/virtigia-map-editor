import { DialogOptionResource } from './DialogOption.resource';

export interface DialogResource {
    id?: number | null;
    groupId: number;
    title: string;
    content: string;
    options: DialogOptionResource[];
}
