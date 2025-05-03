export interface DoorResource {
    id: number
    map_id: number
    x: number
    y: number
    go_map_id: number
    go_x: number
    go_y: number
    min_lvl: number | null
    max_lvl: number | null

    name: string
    double_sided: boolean
}
