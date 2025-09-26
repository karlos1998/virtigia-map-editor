export interface ShopResource {
    id: number
    name: string
    binds_items_permanently: boolean
    buy_price_percent: number;
    sell_price_percent: number;
    max_buy_price: number;
}
