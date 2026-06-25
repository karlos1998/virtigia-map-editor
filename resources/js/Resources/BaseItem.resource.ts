import {ShopResource} from "./Shop.resource";
import {BaseNpcResource} from "./BaseNpc.resource";

export interface BaseItemUsageSource {
    type: 'shop' | 'loot'
    shop: {
        id: number
        name: string
    } | null
    dialog: {
        id: number
        name: string | null
    } | null
    npc: {
        id: number
        name: string
        src: string | null
    } | null
    location: {
        map_id: number
        map_name: string
        x: number
        y: number
        label: string
    } | null
}

export interface BaseItemResource {
    id: number
    name: string

    src: string

    attributes: Record<string, any>

    in_use: boolean

    category: string //todo to moze byc enum w sumie
    category_name?: string

    rarity: string

    price: number
    currency: string
    currency_name?: string
    specific_currency_price: number | null;
    attribute_points?: Record<string, any> | null
    manual_attribute_points?: Record<string, any> | null
    reverse_attributes?: Record<string, any> | null
    need_professions?: (string | null)[]
    need_level?: number | null
    usage_sources: BaseItemUsageSource[]
    usage_source_count: number
    // ...
}

export type BaseItemWithPosition = BaseItemResource & {
    position: number
}

export type BaseItemWithRelations = BaseItemResource & {
    shops: ShopResource[]
    baseNpcs: BaseNpcResource[]
}
