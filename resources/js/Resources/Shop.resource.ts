import {BaseItemResource} from "./BaseItem.resource";
import {DialogResource} from "./Dialog.resource";
import {NpcResource} from "./Npc.resource";

export interface ShopResource {
    id: number
    name: string
    binds_items_permanently: boolean
    buy_price_percent: number;
    sell_price_percent: number;
    max_buy_price: number;
    currency_item_id: number | null;
    currency_item: BaseItemResource | null;
    dialogs: DialogResource[];
    npcs: NpcResource[];
}
