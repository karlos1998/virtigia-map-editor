import {RespawnPointResource} from "@/Resources/RespawnPoint.resource";

export interface MapResource {
    id: number
    name: string
    path: string
    src: string
    thumbnail_src?: string
    x: number
    y: number
    col: string
    pvp: number
    water?: string
    battleground?: string
    battleground2?: string
    battleground3?: string | null

    // ....
    is_teleport_locked?: boolean
    is_city: boolean
    is_grouping_locked?: boolean
    is_internal?: boolean
    map_group?: string
    respawn_point?: RespawnPointResource
}
