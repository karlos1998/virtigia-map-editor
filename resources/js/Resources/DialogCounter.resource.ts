export type DialogCounterScope = 'character' | 'user' | 'global'

export interface DialogCounterResource {
    id: number
    name: string
    scope: DialogCounterScope
}

export const dialogCounterScopeLabels: Record<DialogCounterScope, string> = {
    character: 'Postać',
    user: 'Użytkownik',
    global: 'Globalny',
}

export const dialogCounterScopeOptions = Object.entries(dialogCounterScopeLabels).map(([value, label]) => ({
    value: value as DialogCounterScope,
    label,
}))
