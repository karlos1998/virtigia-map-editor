import {RespawnPointResource} from "@/Resources/RespawnPoint.resource";

export interface MapResource {
    id: number
    name: string
    src: string
    thumbnail_src?: string
    x: number
    y: number
    col: string
    pvp: number
    water?: string
    battleground?: string
    battleground2?: string

    // ....
    is_teleport_locked?: boolean
    is_grouping_locked?: boolean
    respawn_point?: RespawnPointResource
}
