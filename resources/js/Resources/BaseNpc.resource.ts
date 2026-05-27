import {BaseNpcCategoryEnum} from "../Enums/BaseNpcCategory.enum";
import {BaseNpcRankEnum} from "../Enums/BaseNpcRank.enum";
import {ProfessionEnum} from "../Enums/Profession.enum";
import {BaseItemResource} from "./BaseItem.resource";
import {SpecialAttackResource} from "./SpecialAttack.resource";

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
    divine_intervention: boolean | null
    draw_offset_x: number
    draw_offset_y: number
    drop_chances: number[] | null
    min_respawn_time: number | null
    max_respawn_time: number | null
}

export interface BaseNpcWithLoots extends BaseNpcResource {
    loots: BaseItemResource[]
    guaranteed_loot: boolean
    special_attacks: SpecialAttackResource[]
    mob_species?: {
        id: number
        name: string
    }[]
    seasonal_events?: {
        id: number
        name: string
        slug: string
        active: boolean | null
        starts_at: string | null
        ends_at: string | null
    }[]
}
