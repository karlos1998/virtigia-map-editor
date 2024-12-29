import {DialogResource} from "./Dialog.resource";

export type Details = {
    dialog?: DialogResource
}
export interface NpcResource {
    id: number
    name: string
    src: string
    lvl: string
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
        x: number
        y: number
    }[]
}

export type NpcWithLocationsResource = NpcResource & LocationsDto;

export type PureNpcWithOnlyLocationsResource = {
    id: number
} & LocationsDto;
