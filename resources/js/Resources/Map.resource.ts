import {RespawnPointResource} from "@/Resources/RespawnPoint.resource";

export interface MapResource {
    id: number
    name: string
    src: string
    x: number
    y: number
    col: string
    pvp: number
    water?: string

    // ....

    respawn_point?: RespawnPointResource
}
