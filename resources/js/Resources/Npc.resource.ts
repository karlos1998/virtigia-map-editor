export interface NpcResource {
    id: number
    name: string
    src: string
    lvl: string
    //...
}

export type NpcWithLocationResource = NpcResource & {
    location: {
        x: number
        y: number
    }
}

export type PureNpcWithOnlyLocationsResource = {
    id: number
} & {
    locations: {
        id: number
        map_id: number
        map_name: string
        x: number
        y: number
    }[]
}
