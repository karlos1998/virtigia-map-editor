import {ShopResource} from "./Shop.resource";
import {BaseNpcResource} from "./BaseNpc.resource";

export interface BaseItemResource {
    id: number
    name: string

    src: string

    attributes: object

    in_use: boolean
    // ...
}

export type BaseItemWithPosition = BaseItemResource & {
    position: number
}

export type BaseItemWithRelations = BaseItemResource & {
    shops: ShopResource[]
    baseNpcs: BaseNpcResource[]
}
