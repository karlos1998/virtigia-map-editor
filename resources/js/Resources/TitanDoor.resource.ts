interface MapInfo {
    id: number
    name: string
}

interface TitanInfo {
    id: number
    name: string
    level: number
}

export interface TitanDoorResource {
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
    source_map?: MapInfo
    target_map?: MapInfo
    titan?: TitanInfo
    required_base_item?: any
}
