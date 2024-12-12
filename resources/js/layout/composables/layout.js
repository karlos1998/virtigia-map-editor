import { computed, reactive, ref, watch } from 'vue';

const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'emerald',
    surface: null,
    darkTheme: false,
    menuMode: 'drawer',
    menuTheme: 'light'
});

const layoutState = reactive({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    rightMenuActive: false,
    topbarMenuActive: false,
    sidebarActive: false,
    anchored: false,
    activeMenuItem: null,
    overlaySubmenuActive: false
});

const outsideClickListener = ref(null);

export function useLayout() {
    const setPrimary = (value) => {
        layoutConfig.primary = value;
    };

    const setSurface = (value) => {
        layoutConfig.surface = value;
    };

    const setPreset = (value) => {
        layoutConfig.preset = value;
    };

    const setMenuMode = (mode) => {
        layoutConfig.menuMode = mode;

        if (mode === 'static') {
            layoutState.staticMenuDesktopInactive = false;
        }
        if (mode === 'horizontal') {
            if (!layoutConfig.darkTheme) {
                if (!document.startViewTransition) {
                    layoutConfig.menuTheme = 'light';

                    return;
                }

                document.startViewTransition(() => (layoutConfig.menuTheme = 'light'));
            } else {
                if (!document.startViewTransition) {
                    layoutConfig.menuTheme = 'dark';

                    return;
                }

                document.startViewTransition(() => (layoutConfig.menuTheme = 'dark'));
            }
        }
    };

    const showConfigSidebar = () => {
        layoutState.configSidebarVisible = true;
    };

    const showSidebar = () => {
        layoutState.rightMenuActive = true;
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();

            return;
        }

        document.startViewTransition(() => executeDarkModeToggle(event));
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        layoutConfig.menuTheme = isDarkTheme.value ? 'dark' : 'light';

        document.documentElement.classList.toggle('app-dark');
    };

    const setActiveMenuItem = (item) => {
        layoutState.activeMenuItem = item.value || item;
    };

    const setMenuStates = (value) => {
        layoutState.overlaySubmenuActive = value;
        layoutState.menuHoverActive = value;
    };

    const setStaticMenuMobile = () => {
        layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
    };

    const watchSidebarActive = () => {
        watch(isSidebarActive, (newVal) => {
            if (newVal) {
                bindOutsideClickListener();
            } else {
                unbindOutsideClickListener();
            }
        });
    };

    const onMenuToggle = () => {
        if (layoutConfig.menuMode === 'overlay') {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
        } else {
            layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
        }
    };

    const onConfigSidebarToggle = () => {
        if (isSidebarActive.value) {
            resetMenu();
            unbindOutsideClickListener();
        }

        layoutState.configSidebarVisible = !layoutState.configSidebarVisible;
    };

    const onSidebarToggle = (value) => {
        layoutState.sidebarActive = value;
    };

    const onAnchorToggle = () => {
        layoutState.anchored = !layoutState.anchored;
    };

    const bindOutsideClickListener = () => {
        if (!outsideClickListener.value) {
            outsideClickListener.value = (event) => {
                if (isOutsideClicked(event)) {
                    resetMenu();
                }
            };
            document.addEventListener('click', outsideClickListener.value);
        }
    };

    const unbindOutsideClickListener = () => {
        if (outsideClickListener.value) {
            document.removeEventListener('click', outsideClickListener.value);
            outsideClickListener.value = null;
        }
    };

    const isOutsideClicked = (event) => {
        const sidebarEl = document.querySelector('.layout-sidebar');
        const topbarButtonEl = document.querySelector('.topbar-start > button');

        return !(sidebarEl?.isSameNode(event.target) || sidebarEl?.contains(event.target) || topbarButtonEl?.isSameNode(event.target) || topbarButtonEl?.contains(event.target));
    };

    const resetMenu = () => {
        layoutState.overlayMenuActive = false;
        layoutState.overlaySubmenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
        layoutState.configSidebarVisible = false;
    };

    const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive || layoutState.overlaySubmenuActive);

    const isDesktop = computed(() => window.innerWidth > 991);

    const isSlim = computed(() => layoutConfig.menuMode === 'slim');
    const isSlimPlus = computed(() => layoutConfig.menuMode === 'slim-plus');
    const isHorizontal = computed(() => layoutConfig.menuMode === 'horizontal');
    const isOverlay = computed(() => layoutConfig.menuMode === 'overlay');

    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const getPrimary = computed(() => layoutConfig.primary);
    const getSurface = computed(() => layoutConfig.surface);


    const containerClass = computed(() => {
        return [
            {
                'layout-light': !layoutConfig.darkTheme,
                'layout-dark': layoutConfig.darkTheme,
                'layout-light-menu': layoutConfig.menuTheme === 'light',
                'layout-dark-menu': layoutConfig.menuTheme === 'dark',
                'layout-overlay': layoutConfig.menuMode === 'overlay',
                'layout-static': layoutConfig.menuMode === 'static',
                'layout-slim': layoutConfig.menuMode === 'slim',
                'layout-slim-plus': layoutConfig.menuMode === 'slim-plus',
                'layout-horizontal': layoutConfig.menuMode === 'horizontal',
                'layout-reveal': layoutConfig.menuMode === 'reveal',
                'layout-drawer': layoutConfig.menuMode === 'drawer',
                'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
                'layout-overlay-active': layoutState.overlayMenuActive,
                'layout-mobile-active': layoutState.staticMenuMobileActive,
                'layout-sidebar-active': layoutState.sidebarActive,
                'layout-sidebar-anchored': layoutState.anchored
            }
        ];
    })

    return {
        layoutConfig,
        layoutState,
        getPrimary,
        getSurface,
        isDarkTheme,
        setPrimary,
        setSurface,
        setPreset,
        setMenuMode,
        toggleDarkMode,
        onMenuToggle,
        setMenuStates,
        setStaticMenuMobile,
        watchSidebarActive,
        isSidebarActive,
        setActiveMenuItem,
        onConfigSidebarToggle,
        onSidebarToggle,
        onAnchorToggle,
        isSlim,
        isSlimPlus,
        isHorizontal,
        isDesktop,
        isOverlay,
        showConfigSidebar,
        showSidebar,
        unbindOutsideClickListener,
        containerClass
    };
}
