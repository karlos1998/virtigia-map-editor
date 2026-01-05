export interface SpecialAttackResource {
    id: number
    name: string
    attack_type: string
    charge_turns: number
    target: string
    random_target: boolean
    base_npcs_count: number

    effects?: {
        type: string
        value: number
        duration: number
    }[]

    damages?: {
        element: string
        min_damage: number
        max_damage: number
    }[]
}

export interface SpecialAttackWithRelations extends SpecialAttackResource {
    effects: {
        type: string
        value: number
        duration: number
    }[]

    damages: {
        element: string
        min_damage: number
        max_damage: number
    }[]

    baseNpcs: {
        id: number
        name: string
        lvl: number
        profession: string
        rank: string
        category: string
        is_aggressive: boolean
        location_count: number
    }[]
}
