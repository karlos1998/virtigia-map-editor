<script setup>
import { useLayout } from '@/layout/composables/layout';
import { $t, updatePreset, updateSurfacePalette } from '@primevue/themes';
import Aura from '@primevue/themes/aura';
import Lara from '@primevue/themes/lara';
import { ref, onMounted, onBeforeMount, watch, computed } from 'vue';
import { useLayoutStore, layoutLoaded } from '@/stores/layout.store.js';

defineProps({
    simple: {
        type: Boolean,
        default: false
    },
    showButton: {
        type: Boolean,
        default: true,
    }
});

// const { layoutState, layoutConfig, setPrimary, setSurface, setPreset, setMenuMode, onConfigSidebarToggle, toggleDarkMode, isDarkTheme } = useLayout();
const layout = useLayout();


const presets = {
    Aura,
    Lara
};
const presetOptions = ref(Object.keys(presets));
const preset = ref(layout.layoutConfig.preset);
const themeOptions = ref([
    { name: 'Light', value: false },
    { name: 'Dark', value: true }
]);

const darkTheme = ref(layout.layoutConfig.darkTheme);
const menuMode = ref(layout.layoutConfig.menuMode);

const primaryColors = ref([
    { name: 'noir', palette: {} },
    { name: 'emerald', palette: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b', 950: '#022c22' } },
    { name: 'green', palette: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d', 950: '#052e16' } },
    { name: 'lime', palette: { 50: '#f7fee7', 100: '#ecfccb', 200: '#d9f99d', 300: '#bef264', 400: '#a3e635', 500: '#84cc16', 600: '#65a30d', 700: '#4d7c0f', 800: '#3f6212', 900: '#365314', 950: '#1a2e05' } },
    { name: 'orange', palette: { 50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 400: '#fb923c', 500: '#f97316', 600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12', 950: '#431407' } },
    { name: 'amber', palette: { 50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f', 950: '#451a03' } },
    { name: 'yellow', palette: { 50: '#fefce8', 100: '#fef9c3', 200: '#fef08a', 300: '#fde047', 400: '#facc15', 500: '#eab308', 600: '#ca8a04', 700: '#a16207', 800: '#854d0e', 900: '#713f12', 950: '#422006' } },
    { name: 'teal', palette: { 50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e', 800: '#115e59', 900: '#134e4a', 950: '#042f2e' } },
    { name: 'cyan', palette: { 50: '#ecfeff', 100: '#cffafe', 200: '#a5f3fc', 300: '#67e8f9', 400: '#22d3ee', 500: '#06b6d4', 600: '#0891b2', 700: '#0e7490', 800: '#155e75', 900: '#164e63', 950: '#083344' } },
    { name: 'sky', palette: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e', 950: '#082f49' } },
    { name: 'blue', palette: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a', 950: '#172554' } },
    { name: 'indigo', palette: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81', 950: '#1e1b4b' } },
    { name: 'violet', palette: { 50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe',
            300: '#c4b5fd',
            400: '#a78bfa',
            500: '#8b5cf6',
            600: '#7c3aed',
            700: '#6d28d9',
            800: '#5b21b6',
            900: '#4c1d95',
            950: '#2e1065'
        }
    },
    {
        name: 'purple',
        palette: {
            50: '#faf5ff',
            100: '#f3e8ff',
            200: '#e9d5ff',
            300: '#d8b4fe',
            400: '#c084fc',
            500: '#a855f7',
            600: '#9333ea',
            700: '#7e22ce',
            800: '#6b21a8',
            900: '#581c87',
            950: '#3b0764'
        }
    },
    {
        name: 'fuchsia',
        palette: {
            50: '#fdf4ff',
            100: '#fae8ff',
            200: '#f5d0fe',
            300: '#f0abfc',
            400: '#e879f9',
            500: '#d946ef',
            600: '#c026d3',
            700: '#a21caf',
            800: '#86198f',
            900: '#701a75',
            950: '#4a044e'
        }
    },
    {
        name: 'pink',
        palette: {
            50: '#fdf2f8',
            100: '#fce7f3',
            200: '#fbcfe8',
            300: '#f9a8d4',
            400: '#f472b6',
            500: '#ec4899',
            600: '#db2777',
            700: '#be185d',
            800: '#9d174d',
            900: '#831843',
            950: '#500724'
        }
    },
    {
        name: 'rose',
        palette: {
            50: '#fff1f2',
            100: '#ffe4e6',
            200: '#fecdd3',
            300: '#fda4af',
            400: '#fb7185',
            500: '#f43f5e',
            600: '#e11d48',
            700: '#be123c',
            800: '#9f1239',
            900: '#881337',
            950: '#4c0519'
        }
    }
]);

const surfaces = ref([
    {
        name: 'slate',
        palette: {
            0: '#ffffff',
            50: '#f8fafc',
            100: '#f1f5f9',
            200: '#e2e8f0',
            300: '#cbd5e1',
            400: '#94a3b8',
            500: '#64748b',
            600: '#475569',
            700: '#334155',
            800: '#1e293b',
            900: '#0f172a',
            950: '#020617'
        }
    },
    {
        name: 'gray',
        palette: {
            0: '#ffffff',
            50: '#f9fafb',
            100: '#f3f4f6',
            200: '#e5e7eb',
            300: '#d1d5db',
            400: '#9ca3af',
            500: '#6b7280',
            600: '#4b5563',
            700: '#374151',
            800: '#1f2937',
            900: '#111827',
            950: '#030712'
        }
    },
    {
        name: 'zinc',
        palette: {
            0: '#ffffff',
            50: '#fafafa',
            100: '#f4f4f5',
            200: '#e4e4e7',
            300: '#d4d4d8',
            400: '#a1a1aa',
            500: '#71717a',
            600: '#52525b',
            700: '#3f3f46',
            800: '#27272a',
            900: '#18181b',
            950: '#09090b'
        }
    },
    {
        name: 'neutral',
        palette: {
            0: '#ffffff',
            50: '#fafafa',
            100: '#f5f5f5',
            200: '#e5e5e5',
            300: '#d4d4d4',
            400: '#a3a3a3',
            500: '#737373',
            600: '#525252',
            700: '#404040',
            800: '#262626',
            900: '#171717',
            950: '#0a0a0a'
        }
    },
    {
        name: 'stone',
        palette: {
            0: '#ffffff',
            50: '#fafaf9',
            100: '#f5f5f4',
            200: '#e7e5e4',
            300: '#d6d3d1',
            400: '#a8a29e',
            500: '#78716c',
            600: '#57534e',
            700: '#44403c',
            800: '#292524',
            900: '#1c1917',
            950: '#0c0a09'
        }
    },
    {
        name: 'soho',
        palette: {
            0: '#ffffff',
            50: '#f4f4f4',
            100: '#e8e9e9',
            200: '#d2d2d4',
            300: '#bbbcbe',
            400: '#a5a5a9',
            500: '#8e8f93',
            600: '#77787d',
            700: '#616268',
            800: '#4a4b52',
            900: '#34343d',
            950: '#1d1e27'
        }
    },
    {
        name: 'viva',
        palette: {
            0: '#ffffff',
            50: '#f3f3f3',
            100: '#e7e7e8',
            200: '#cfd0d0',
            300: '#b7b8b9',
            400: '#9fa1a1',
            500: '#87898a',
            600: '#6e7173',
            700: '#565a5b',
            800: '#3e4244',
            900: '#262b2c',
            950: '#0e1315'
        }
    },
    {
        name: 'ocean',
        palette: {
            0: '#ffffff',
            50: '#fbfcfc',
            100: '#F7F9F8',
            200: '#EFF3F2',
            300: '#DADEDD',
            400: '#B1B7B6',
            500: '#828787',
            600: '#5F7274',
            700: '#415B61',
            800: '#29444E',
            900: '#183240',
            950: '#0c1920'
        }
    }
]);

function getPresetExt() {
    const color = primaryColors.value.find((c) => c.name === layout.layoutConfig.primary);

    if (color.name === 'noir') {
        return {
            semantic: {
                primary: {
                    50: '{surface.50}',
                    100: '{surface.100}',
                    200: '{surface.200}',
                    300: '{surface.300}',
                    400: '{surface.400}',
                    500: '{surface.500}',
                    600: '{surface.600}',
                    700: '{surface.700}',
                    800: '{surface.800}',
                    900: '{surface.900}',
                    950: '{surface.950}'
                },
                colorScheme: {
                    light: {
                        primary: {
                            color: '{primary.950}',
                            contrastColor: '#ffffff',
                            hoverColor: '{primary.800}',
                            activeColor: '{primary.700}'
                        },
                        highlight: {
                            background: '{primary.950}',
                            focusBackground: '{primary.700}',
                            color: '#ffffff',
                            focusColor: '#ffffff'
                        }
                    },
                    dark: {
                        primary: {
                            color: '{primary.50}',
                            contrastColor: '{primary.950}',
                            hoverColor: '{primary.200}',
                            activeColor: '{primary.300}'
                        },
                        highlight: {
                            background: '{primary.50}',
                            focusBackground: '{primary.300}',
                            color: '{primary.950}',
                            focusColor: '{primary.950}'
                        }
                    }
                }
            }
        };
    } else {
        return {
            semantic: {
                primary: color.palette,
                colorScheme: {
                    light: {
                        primary: {
                            color: '{primary.500}',
                            contrastColor: '#ffffff',
                            hoverColor: '{primary.600}',
                            activeColor: '{primary.700}'
                        },
                        highlight: {
                            background: '{primary.50}',
                            focusBackground: '{primary.100}',
                            color: '{primary.700}',
                            focusColor: '{primary.800}'
                        }
                    },
                    dark: {
                        primary: {
                            color: '{primary.400}',
                            contrastColor: '{surface.900}',
                            hoverColor: '{primary.300}',
                            activeColor: '{primary.200}'
                        },
                        highlight: {
                            background: 'color-mix(in srgb, {primary.400}, transparent 84%)',
                            focusBackground: 'color-mix(in srgb, {primary.400}, transparent 76%)',
                            color: 'rgba(255,255,255,.87)',
                            focusColor: 'rgba(255,255,255,.87)'
                        }
                    }
                }
            }
        };
    }
}

function updateColors(type, color) {
    if (type === 'primary') {
        layout.setPrimary(color.name);
    } else if (type === 'surface') {
        layout.setSurface(color.name);
    }

    applyTheme(type, color);
}

function applyTheme(type, color) {
    if (type === 'primary') {
        updatePreset(getPresetExt());
    } else if (type === 'surface') {
        updateSurfacePalette(color.palette);
    }
}

// function onPresetChange() {
//     setPreset(preset.value);
//     const presetValue = presets[preset.value];
//     const surfacePalette = surfaces.value.find((s) => s.name === layoutConfig.surface)?.palette;
//
//     $t().preset(presetValue).preset(getPresetExt()).surfacePalette(surfacePalette).use({ useDefaultOptions: true });
// }


/*
 * Poprawka tego, czego primevue (freya) nie raczyla zrobic....
 */
const layoutStore = useLayoutStore()

const loadLayoutConfigFromStorage = () => {
    // First check if we have settings in the store
    if (layoutStore.darkTheme !== undefined) {
        // Apply dark theme if it's set in the store
        if (layoutStore.darkTheme) {
            if (!document.documentElement.classList.contains('app-dark')) {
                document.documentElement.classList.add('app-dark');
            }
            layout.layoutConfig.darkTheme = true;
            layout.layoutConfig.menuTheme = 'dark';
            darkTheme.value = true;
        } else {
            if (document.documentElement.classList.contains('app-dark')) {
                document.documentElement.classList.remove('app-dark');
            }
            layout.layoutConfig.darkTheme = false;
            layout.layoutConfig.menuTheme = layoutStore.menuTheme || 'light';
            darkTheme.value = false;
        }
    } else if (document.documentElement.classList.contains('app-dark')) {
        // Fallback to checking the DOM if store doesn't have the setting
        layout.layoutConfig.darkTheme = true;
        layout.layoutConfig.menuTheme = 'dark';
        darkTheme.value = true;
    }

    // Apply primary color
    if (layoutStore.primary) {
        layout.layoutConfig.primary = layoutStore.primary;
        const primary = primaryColors.value.find(color => color.name === layoutStore.primary);
        if (primary) {
            updateColors('primary', primary);
        }
    }

    // Apply surface color
    if (layoutStore.surface) {
        layout.layoutConfig.surface = layoutStore.surface;
        const surface = surfaces.value.find(color => color.name === layoutStore.surface);
        if (surface) {
            updateColors('surface', surface);
        }
    }

    // Apply menu mode
    if (layoutStore.menuMode) {
        layout.layoutConfig.menuMode = layoutStore.menuMode;
        menuMode.value = layoutStore.menuMode;
        layout.setMenuMode(layoutStore.menuMode);
    }

    // Apply menu theme
    if (layoutStore.menuTheme) {
        layout.layoutConfig.menuTheme = layoutStore.menuTheme;
    }

    // Apply preset
    if (layoutStore.preset) {
        layout.layoutConfig.preset = layoutStore.preset;
        preset.value = layoutStore.preset;
    }
}

if(!layoutLoaded.value) {
    layoutLoaded.value = true;
    loadLayoutConfigFromStorage();

    //zapis do local localstorage i pinii konfiguracji motywu
    watch(layout.layoutConfig, (newValue) => {
        // Synchronize all layout config properties with the Pinia store
        for (const key in newValue) {
            if (newValue.hasOwnProperty(key)) {
                layoutStore[key] = newValue[key];
            }
        }

        // Save dark theme setting to localStorage for quick access on page load
        localStorage.setItem('is_theme_dark', newValue.darkTheme ? '1' : '0');

        // Ensure the DOM class is in sync with the dark theme setting
        if (newValue.darkTheme) {
            if (!document.documentElement.classList.contains('app-dark')) {
                document.documentElement.classList.add('app-dark');
            }
        } else {
            if (document.documentElement.classList.contains('app-dark')) {
                document.documentElement.classList.remove('app-dark');
            }
        }
    }, { deep: true }) // Use deep watching to catch nested property changes

    //zmiana motywu systemowego
    window.matchMedia("(prefers-color-scheme: dark)").addEventListener('change', (event) => {
        const preferSystemDarkTheme = event.matches;
        if(layout.layoutConfig.darkTheme !== preferSystemDarkTheme) {
            layout.toggleDarkMode()
        }
    });
}

/////////////////////////////////////////////////////////////////////



</script>

<template>
    <button v-if="showButton" class="layout-config-button config-link" type="button" @click="layout.onConfigSidebarToggle">
        <i class="pi pi-cog"></i>
    </button>

    <Drawer
        v-model:visible="layout.layoutState.configSidebarVisible"
        position="right"
        class="layout-config-sidebar w-80"
        header="Settings"
        :pt="{
            pcCloseButton: { root: 'ml-auto' }
        }"
    >
        <div class="flex flex-col gap-4">
            <div>
                <span class="text-lg text-muted-color font-semibold">Primary</span>
                <div class="pt-2 flex gap-2 flex-wrap">
                    <button
                        v-for="primaryColor of primaryColors"
                        :key="primaryColor.name"
                        type="button"
                        @click="updateColors('primary', primaryColor)"
                        :class="['cursor-pointer w-6 h-6 rounded-full flex flex-shrink-0 items-center justify-center p-0 outline-none outline-offset-1', { 'outline-primary': layout.layoutConfig.primary === primaryColor.name }]"
                        :style="{ backgroundColor: `${primaryColor.name === 'noir' ? 'var(--text-color)' : primaryColor.palette['500']}` }"
                    ></button>
                </div>
            </div>

            <div>
                <span class="text-lg text-muted-color font-semibold">Surface</span>
                <div class="pt-2 flex gap-2 flex-wrap">
                    <button
                        v-for="surface of surfaces"
                        :key="surface.name"
                        type="button"
                        @click="updateColors('surface', surface)"
                        :class="[
                            'cursor-pointer w-6 h-6 rounded-full flex flex-shrink-0 items-center justify-center p-0 outline-none outline-offset-1',
                            { 'outline-primary': layout.layoutConfig.surface ? layout.layoutConfig.surface === surface.name : layout.isDarkTheme ? surface.name === 'zinc' : surface.name === 'slate' }
                        ]"
                        :style="{ backgroundColor: `${surface.palette['500']}` }"
                    ></button>
                </div>
            </div>

<!--            <div>-->
<!--                <div class="flex flex-col gap-2">-->
<!--                    <span class="text-lg text-muted-color font-semibold">Presets</span>-->
<!--                    <SelectButton v-model="preset" @change="onPresetChange" :options="presetOptions"-->
<!--                                  :allowEmpty="false" />-->
<!--                </div>-->
<!--            </div>-->

            <div>
                <div class="flex flex-col gap-2">
                    <span class="text-lg text-muted-color font-semibold">Color Scheme</span>
                    <SelectButton v-model="darkTheme" @change="layout.toggleDarkMode" :options="themeOptions"
                                  optionLabel="name" optionValue="value" :allowEmpty="false" />
                </div>
            </div>

            <template v-if="!simple">
                <div>
                    <div class="flex flex-col gap-2">
                        <span class="text-lg text-muted-color font-semibold">Menu Type</span>
                        <div class="flex flex-wrap flex-col gap-3">
                            <div class="flex">
                                <div class="flex items-center gap-2 w-1/2">
                                    <RadioButton name="menuMode" value="static" v-model="menuMode"
                                                 @update:modelValue="layout.setMenuMode" inputId="mode1"></RadioButton>
                                    <label for="mode1">Static</label>
                                </div>

                                <div class="flex items-center gap-2 w-1/2">
                                    <RadioButton name="menuMode" value="overlay" v-model="menuMode"
                                                 @update:modelValue="layout.setMenuMode" inputId="mode2"></RadioButton>
                                    <label for="mode2">Overlay</label>
                                </div>
                            </div>
<!--                            <div class="flex">-->
<!--                                <div class="flex items-center gap-2 w-1/2">-->
<!--                                    <RadioButton name="menuMode" value="slim" v-model="menuMode"-->
<!--                                                 @update:modelValue="layout.setMenuMode" inputId="mode3"></RadioButton>-->
<!--                                    <label for="mode2">Slim</label>-->
<!--                                </div>-->
<!--                                <div class="flex items-center gap-2 w-1/2">-->
<!--                                    <RadioButton name="menuMode" value="slim-plus" v-model="menuMode"-->
<!--                                                 @update:modelValue="layout.setMenuMode" inputId="mode4"></RadioButton>-->
<!--                                    <label for="mode3">Slim+</label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="flex">-->
<!--                                <div class="flex items-center gap-2 w-1/2">-->
<!--                                    <RadioButton name="menuMode" value="reveal" v-model="menuMode"-->
<!--                                                 @update:modelValue="layout.setMenuMode" inputId="mode5"></RadioButton>-->
<!--                                    <label for="mode4">Reveal</label>-->
<!--                                </div>-->
<!--                                <div class="flex items-center gap-2 w-1/2">-->
<!--                                    <RadioButton name="menuMode" value="drawer" v-model="menuMode"-->
<!--                                                 @update:modelValue="layout.setMenuMode" inputId="mode6"></RadioButton>-->
<!--                                    <label for="mode5">Drawer</label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="flex">-->
<!--                                <div class="flex items-center gap-2 w-1/2">-->
<!--                                    <RadioButton name="menuMode" value="horizontal" v-model="menuMode"-->
<!--                                                 @update:modelValue="layout.setMenuMode" inputId="mode7"></RadioButton>-->
<!--                                    <label for="mode7">Horizontal</label>-->
<!--                                </div>-->
<!--                            </div>-->
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex flex-col gap-2">
                        <span class="text-lg text-muted-color font-semibold">Menu Theme</span>
                        <div class="flex flex-wrap flex-col gap-4">
                            <div class="flex">
                                <div class="flex items-center gap-2 w-1/2">
                                    <RadioButton name="menuTheme" value="light" v-model="layout.layoutConfig.menuTheme"
                                                 inputId="menu-light"
                                                 :disabled="layout.layoutConfig.menuMode === 'horizontal' && layout.isDarkTheme"></RadioButton>
                                    <label for="menu-light">Light</label>
                                </div>
                                <div class="flex items-center gap-2 w-1/2">
                                    <RadioButton name="menuTheme" value="dark" v-model="layout.layoutConfig.menuTheme"
                                                 inputId="menu-dark"
                                                 :disabled="layout.layoutConfig.menuMode === 'horizontal' && !layout.isDarkTheme"></RadioButton>
                                    <label for="menu-dark">Dark</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </Drawer>
</template>
