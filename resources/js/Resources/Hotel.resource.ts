import { BaseItemResource } from "@/Resources/BaseItem.resource";
import { DoorResource } from "@/Resources/Door.resource";

export interface HotelRoomResource {
    id: number
    hotel_id: number
    base_item_id: number
    door_id: number
    price: number
    base_item?: BaseItemResource | null
    door?: DoorResource | null
    created_at?: string
    updated_at?: string
}

export interface HotelResource {
    id: number
    name: string
    currency: string
    period: string
    rooms_count: number
    rooms?: HotelRoomResource[]
    created_at?: string
    updated_at?: string
}
