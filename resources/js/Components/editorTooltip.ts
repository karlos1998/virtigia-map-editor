import type {DirectiveBinding} from 'vue';
import {ToolTipDirective} from 'virtigia-tips';

function withStackValue(binding: DirectiveBinding): DirectiveBinding {
    const item = binding.value;
    if (!binding.modifiers.item || item?.stack_value == null) {
        return binding;
    }

    return {...binding, value: {...item, price: item.stack_value}};
}

export const editorTooltip = {
    ...ToolTipDirective,
    mounted(element: HTMLElement, binding: DirectiveBinding) {
        ToolTipDirective.mounted(element, withStackValue(binding));
    },
    updated(element: HTMLElement, binding: DirectiveBinding) {
        ToolTipDirective.updated(element, withStackValue(binding));
    },
};
