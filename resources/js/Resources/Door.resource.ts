import { BaseItemResource } from "@/Resources/BaseItem.resource";
import { MapResource } from "@/Resources/Map.resource";

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
    map?: MapResource | null
    target_map?: MapResource | null
    required_base_item?: BaseItemResource | null
}
