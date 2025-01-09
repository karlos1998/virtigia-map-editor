import {BaseNpcCategoryEnum} from "../Enums/BaseNpcCategory.enum";

export interface BaseNpcResource {
    id: number
    name: string
    src: string
    lvl: number
    location_count: number
    category: BaseNpcCategoryEnum
}
