import {BaseNpcCategoryEnum} from "../Enums/BaseNpcCategory.enum";
import {BaseNpcRankEnum} from "../Enums/BaseNpcRank.enum";
import {ProfessionEnum} from "../Enums/Profession.enum";

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
}
