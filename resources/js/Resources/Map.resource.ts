import {RespawnPointResource} from "@/Resources/RespawnPoint.resource";

export interface MapWeatherSettings {
    rain?: {enabled: boolean; density: number; speed: number; wind: number};
    fog?: {enabled: boolean; opacity: number; speedX: number; speedY: number; color: string};
}

export interface MapResource {
    weather_settings?: MapWeatherSettings | null;
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
