import {BaseNpcCategoryEnum} from "../Enums/BaseNpcCategory.enum";
import {BaseNpcRankEnum} from "../Enums/BaseNpcRank.enum";
import {ProfessionEnum} from "../Enums/Profession.enum";
import {BaseItemResource} from "./BaseItem.resource";

export interface BaseNpcResource {
    id: number
    name: string
    src: string
    lvl: number
    location_count: number
    category: BaseNpcCategoryEnum
    rank: BaseNpcRankEnum
    profession: ProfessionEnum
    profession_name: string
    is_aggressive: boolean
}

export interface BaseNpcWithLoots extends BaseNpcResource {
    loots: BaseItemResource[]
}
