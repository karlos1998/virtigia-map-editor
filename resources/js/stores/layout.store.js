import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useLayoutStore = defineStore('layout', {
    state: () => ({

        //uwaga! nie zmieniaj wartosci domyslnych.
        preset: 'Aura',
        primary: 'emerald',
        surface: null,
        darkTheme: false,
        menuMode: 'drawer',
        menuTheme: 'light',
        anchored: false

    }),
    persist: true,
})


export const layoutLoaded = ref(false);
