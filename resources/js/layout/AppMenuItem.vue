<script setup>
import { ref, onBeforeMount, watch, nextTick, computed } from 'vue';
import { useLayout } from '@/layout/composables/layout';
import { calculateScrollbarWidth } from '@primeuix/utils/dom';
import { router } from '@inertiajs/vue3';

const { layoutConfig, layoutState, setActiveMenuItem, onMenuToggle, setMenuStates, setStaticMenuMobile, isHorizontal, isSlim, isSlimPlus, isDesktop, isOverlay, isStatic } = useLayout();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    },
    index: {
        type: Number,
        default: 0
    },
    root: {
        type: Boolean,
        default: true
    },
    parentItemKey: {
        type: String,
        default: null
    }
});

const isActiveMenu = ref(false);
const itemKey = ref(null);
const subMenuRef = ref(null);
const menuItemRef = ref(null);

onBeforeMount(() => {
    itemKey.value = props.parentItemKey ? `${props.parentItemKey}-${props.index}` : String(props.index);

    const activeItem = layoutState.activeMenuItem;
    isActiveMenu.value = activeItem === itemKey.value || (activeItem ? activeItem.startsWith(`${itemKey.value}-`) : false);
    handleRouteChange(window.location.pathname);
});

watch(
    () => isActiveMenu.value,
    () => {
        const rootIndex = props.root ? props.index : parseInt(`${props.parentItemKey}`[0], 10);
        const overlay = document.querySelectorAll('.layout-root-submenulist')[rootIndex];
        const target = document.querySelectorAll('.layout-root-menuitem')[rootIndex];

        if ((isSlim.value || isSlimPlus.value || isHorizontal.value) && isDesktop.value) {
            nextTick(() => {
                calculatePosition(overlay, target);
            });
        }
    }
);

watch(
    () => layoutState.activeMenuItem,
    (newVal) => {
        isActiveMenu.value = newVal === itemKey.value || newVal.startsWith(`${itemKey.value}-`);
    }
);

watch(
    () => layoutConfig.menuMode,
    () => {
        isActiveMenu.value = false;
    }
);

watch(
    () => layoutState.overlaySubmenuActive,
    (newValue) => {
        if (!newValue) {
            isActiveMenu.value = false;
        }
    }
);

function handleRouteChange(newPath) {
    if (!(isSlim.value || isSlimPlus.value || isHorizontal.value) && props.item.to && props.item.to === newPath) {
        setActiveMenuItem(itemKey);
    } else if (isSlim.value || isSlimPlus.value || isHorizontal.value) {
        isActiveMenu.value = false;
    }
}

async function itemClick(event, item) {
    console.log('itemClick', item.items )
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    if (item.route) {
            router.visit(route(item.route));
            onMenuToggle();
            return;
    }

    if ((item.to || item.url) && (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive)) {
        onMenuToggle();
    }

    if (item.command) {
        item.command({ originalEvent: event, item });
    }

    if (item.items) {
        if (props.root && isActiveMenu.value && (isSlim.value || isSlimPlus.value || isHorizontal.value)) {
            setMenuStates(false);
            return;
        }

        setActiveMenuItem(isActiveMenu.value ? props.parentItemKey : itemKey);

        if (props.root && !isActiveMenu.value && (isSlim.value || isSlimPlus.value || isHorizontal.value)) {
            setMenuStates(true);
            isActiveMenu.value = true;
            removeAllTooltips();
        }
    } else {
        if (!isDesktop.value) {
            setStaticMenuMobile();
        }

        if (isSlim.value || isSlimPlus.value || isHorizontal.value) {
            setMenuStates(false);
            return;
        }

        setActiveMenuItem(itemKey);
    }
}

function onMouseEnter() {
    if (props.root && (isSlim.value || isSlimPlus.value || isHorizontal.value) && isDesktop.value) {
        if (!isActiveMenu.value && layoutState.menuHoverActive) {
            setActiveMenuItem(itemKey);
        }
    }
}

function removeAllTooltips() {
    const tooltips = document.querySelectorAll('.p-tooltip');
    tooltips.forEach((tooltip) => tooltip.remove());
}

function calculatePosition(overlay, target) {
    if (overlay && target) {
        const { left, top } = target.getBoundingClientRect();
        const [vWidth, vHeight] = [window.innerWidth, window.innerHeight];
        const [oWidth, oHeight] = [overlay.offsetWidth, overlay.offsetHeight];
        const scrollbarWidth = calculateScrollbarWidth();

        overlay.style.top = overlay.style.left = '';

        if (isHorizontal.value) {
            const width = left + oWidth + scrollbarWidth;
            overlay.style.left = vWidth < width ? `${left - (width - vWidth)}px` : `${left}px`;
        } else if (isSlim.value || isSlimPlus.value) {
            const height = top + oHeight;
            overlay.style.top = vHeight < height ? `${top - (height - vHeight)}px` : `${top}px`;
        }
    }
}

const pathname = computed(() => window.location.pathname);

</script>

<template>
    <li ref="menuItemRef" :class="{
        'layout-root-menuitem': root,
        'active-menuitem': isStatic || isOverlay ? !root && isActiveMenu : isActiveMenu,
        'horizontal-menuitem': isHorizontal,
        'slim-menuitem': isSlim || isSlimPlus
    }">
        <div v-if="root && item.visible !== false && !isSlim && !isSlimPlus" class="layout-menuitem-root-text">
            <span>{{ item.label }}</span> <i class="layout-menuitem-root-icon"></i>
        </div>
        <a
            v-if="(!item.to || item.items) && item.visible !== false"
            :href="item.url"
            @click="itemClick($event, item)"
            :class="item.class"
            :target="item.target"
            tabindex="0"
            @mouseenter="onMouseEnter"
            class="layout-menuitem-link"
        >
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span v-if="!isSlim && !isSlimPlus" class="layout-menuitem-text">{{ item.label }}</span>
            <span v-if="item.badge && (!isSlim && !isSlimPlus)" :class="['layout-menuitem-badge', item.badgeClass]">{{ item.badge }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items && (!isSlim && !isSlimPlus)"></i>
        </a>
        <a
            v-if="item.to && !item.items && item.visible !== false"
            :href="item.to"
            @click="itemClick($event, item)"
            :class="[item.class, { 'active-route': pathname === item.to }, 'layout-menuitem-link']"
            tabindex="0"
            @mouseenter="onMouseEnter"
        >
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span v-if="!isSlim && !isSlimPlus" class="layout-menuitem-text">{{ item.label }}</span>
            <span v-if="item.badge && (!isSlim && !isSlimPlus)" :class="['layout-menuitem-badge', item.badgeClass]">{{ item.badge }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items && (!isSlim && !isSlimPlus)"></i>
        </a>

        <ul ref="subMenuRef" :class="{ 'layout-root-submenulist': root }" v-if="item.items && item.visible !== false">
            <AppMenuItem v-for="(child, i) in item.items" :key="i" :index="i" :item="child" :parentItemKey="itemKey" :root="false"></AppMenuItem>
        </ul>
    </li>
</template>

<style lang="scss" scoped>
.horizontal-menuitem {
    @apply mx-1;
}

.horizontal-menuitem > .layout-menuitem-link {
    @apply rounded-lg px-3 py-2;
}

.slim-menuitem > .layout-menuitem-link {
    @apply justify-center px-2;
}

.slim-menuitem .layout-menuitem-icon {
    @apply mx-auto;
}

:deep(.layout-horizontal) .layout-root-submenulist {
    @apply absolute top-full left-0 bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-100 dark:border-gray-700 min-w-48 z-50;
}

:deep(.layout-slim) .layout-root-submenulist,
:deep(.layout-slim-plus) .layout-root-submenulist {
    @apply absolute left-full top-0 bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-100 dark:border-gray-700 min-w-48 z-50;
}
</style>
