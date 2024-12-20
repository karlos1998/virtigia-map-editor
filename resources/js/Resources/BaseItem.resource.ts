export interface BaseItemResource {
    id: number
    name: string

    src: string
    // ...
}

export type BaseItemWithPosition = BaseItemResource & {
    position: number
}
