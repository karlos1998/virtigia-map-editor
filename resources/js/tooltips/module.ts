import { DirectiveBinding, nextTick, ref } from 'vue'
import { HtmlPayload, ItemPayload, NpcPayload, OtherPayload } from '../RockTip/typings/payloads';
import {bind} from "axios";
import {useHeroStore} from "../stores/hero.stores";

const heroStore = useHeroStore();

interface ToolTipState {
    opened: boolean
    otherPayload: OtherPayload | false,
    itemPayload: ItemPayload | false,
    htmlPayload: HtmlPayload | false,
    npcPayload: NpcPayload | false,
    positionX: number
    positionY: number
    element: HTMLElement | null
    target: HTMLElement | null
}

const state = ref<ToolTipState>({
    opened: false,
    otherPayload: false,
    itemPayload: false,
    htmlPayload: false,
    npcPayload: false,
    positionX: -9999,
    positionY: -9999,
    element: null,
    target: null
})
type TipPosition = 'top' | 'bottom' | 'left' | 'right'

const reposition = (tipPosition: TipPosition) => {
    if (!state.value.element || !state.value.target) {
        return
    }
    const tip = state.value.element

    let position = getPos(tipPosition)!
    const height = tip.getBoundingClientRect().height
    const width = tip.getBoundingClientRect().width
    const windowHeight = window.innerHeight
    const windowWidth = window.innerWidth

    switch (tipPosition) {
        case 'top':
            if (position.top < 0) {
                position = getPos('bottom')!
            }
            if (position.left < 0) {
                position.left = 0
            }
            if (position.left + width > windowWidth - 10) {
                position.left = windowWidth - width
            }
            break
        case 'bottom':
            if (position.top + height > windowHeight - 10) {
                position = getPos('top')!
            }
            if (position.left < 0) {
                position.left = 0
            }
            if (position.left + width > windowWidth - 10) {
                position.left = windowWidth - width
            }
            break
        case 'left':
            if (position.left < 0) {
                position = getPos('right')!
            }
            if (position.top < 0) {
                position.top = 0
            }
            if (position.top + height > windowHeight - 10) {
                position.top = windowHeight - height
            }
            break
        case 'right':
            if (position.left + width > windowWidth - 10) {
                position = getPos('left')!
            }
            if (position.top < 0) {
                position.top = 0
            }
            if (position.top + height > windowHeight - 10) {
                position.top = windowHeight - height
            }
    }
    state.value.positionX = position.left + window.scrollX
    state.value.positionY = position.top + window.scrollY
}

const getPos = (tipPosition: TipPosition) => {
    const target = state.value.target!

    const targetOffset = target.getBoundingClientRect()
    let { width, height } = targetOffset
    const tipBounds = state.value.element!.getBoundingClientRect()
    const tipWidth = tipBounds.width
    const tipHeight = tipBounds.height

    const position = {
        left: targetOffset.x,
        top: targetOffset.y,
    }
    switch (tipPosition) {
        case 'top':
            position.top -= tipHeight + 2
            position.left -= tipWidth / 2 - width / 2
            break
        case 'bottom':
            position.top += tipHeight + 2
            position.left -= tipWidth / 2 - width / 2
            break
        case 'left':
            position.left -= 2 + width
            position.top -= height / 2 - tipHeight / 2
            break
        case 'right':
            position.left += tipWidth + 2
            position.top -= height / 2 - tipHeight / 2
    }
    return position
}

const updateDataset = (el: HTMLElement, binding: DirectiveBinding) => {
    console.log('binding', heroStore.getLvl(), binding.modifiers, binding.value)
    if (binding.modifiers.npc) {
        el.dataset.npc = JSON.stringify(binding.value)
    } else if (binding.modifiers.item) {
        el.dataset.item = JSON.stringify(binding.value)
    } else if (binding.modifiers.other) {
        el.dataset.other = JSON.stringify(binding.value)
    } else {
        el.dataset.html = binding.value
    }
}

const ToolTipDirective = {
    mounted(el: HTMLElement, binding: DirectiveBinding) {
        updateDataset(el, binding)

        el.addEventListener('mouseenter', async (event: Event) => {
            state.value.target = el
            state.value.opened = true

            state.value.npcPayload = el.dataset.npc ? JSON.parse(el.dataset.npc) : false
            state.value.itemPayload = el.dataset.item ? JSON.parse(el.dataset.item) : false
            state.value.otherPayload = el.dataset.other ? JSON.parse(el.dataset.other) : false
            state.value.htmlPayload = el.dataset.html ? {
                schema: {
                    inner: {
                        content: el.dataset.html
                    }
                }
            } : false
            let tipDirection: TipPosition = 'top';
            if (binding.modifiers.bottom) {
                tipDirection = 'bottom'
            } else if (binding.modifiers.left) {
                tipDirection = 'left'
            } else if (binding.modifiers.right) {
                tipDirection = 'right'
            }

            await nextTick(() => {
                reposition(tipDirection)
            })
        })
        el.addEventListener('mouseout', (event: Event) => {
            const mouseEvent = event as MouseEvent
            /** @ts-ignore */
            if (el.contains(mouseEvent.toElement)) {
                return
            }

            state.value.opened = false
            state.value.positionX = -9999
            state.value.positionY = -9999
            state.value.target = null
        })
    },
    updated(el: HTMLElement, binding: DirectiveBinding) {
        updateDataset(el, binding)
    },
}

const setToolTipElement = (el: HTMLElement) => {
    state.value.element = el
}

export default ToolTipDirective
export const useToolTip = () => {
    return {
        state,
        setToolTipElement,
    }
}
