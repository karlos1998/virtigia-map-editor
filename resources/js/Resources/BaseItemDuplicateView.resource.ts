export interface BaseItemDuplicateListItem {
    id: number
    name: string
    src: string
    in_use: boolean
    usage_source_count: number
}

export interface BaseItemDuplicateViewResource {
    id: number
    name: string
    category: string | null
    category_name: string | null
    rarity: string | null
    rarity_name: string | null
    need_level: number | null
    refreshed_at: string | null
    duplicate_item: BaseItemDuplicateListItem
    used_item: BaseItemDuplicateListItem
}
