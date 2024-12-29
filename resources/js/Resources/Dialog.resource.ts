import {NpcResource} from "./Npc.resource";

export interface DialogResource {
    id: number
    // name: string
    npcs_count: number
}

export type DialogWithNpcsResource = DialogResource & {
    npcs: NpcResource
}
