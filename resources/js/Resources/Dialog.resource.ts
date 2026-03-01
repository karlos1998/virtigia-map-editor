import { NpcWithLocationsResource } from "./Npc.resource";

export interface DialogResource {
    id: number
    name: string
    npcs_count: number
    last_activity_at: string | null
    last_editor_id: number | null
    last_editor_name: string | null
}

export type DialogWithNpcsResource = DialogResource & {
    npcs: NpcWithLocationsResource[]
}
