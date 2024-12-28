export interface NpcResource {
    id: number
    name: string
    src: string
    //...
}

export type NpcWithLocationResource = NpcResource & {
    location: {
        x: number
        y: number
    }
}
