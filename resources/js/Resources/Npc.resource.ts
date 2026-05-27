import {DialogResource} from "./Dialog.resource";

export type Details = {
    dialog?: DialogResource
}
export interface NpcResource {
    id: number
    base_npc_id: number
    name: string
    src: string
    lvl: string
    type: number
    draw_offset_x: number
    draw_offset_y: number
    group_id: number | null
    in_group: boolean
    enabled: boolean
    //...
}

export type NpcWithDetails = NpcResource & Details;

export type NpcWithLocationResource = NpcResource & {
    location: {
        x: number
        y: number
    }
}

type LocationsDto = {
    locations: {
        id: number
        map_id: number
        map_name: string
        map_src: string
        map_width: number
        map_height: number
        x: number
        y: number
    }[]
}

export type NpcWithLocationsResource = NpcResource & LocationsDto;

export type PureNpcWithOnlyLocationsResource = {
    id: number
} & LocationsDto;
