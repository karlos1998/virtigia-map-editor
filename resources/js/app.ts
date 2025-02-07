import './bootstrap';
// import './echo';
// import '../css/app.css';
import '@/assets/styles.scss';
import '@/assets/tailwind.css';

import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

// import BlockViewer from '@/Components/BlockViewer.vue';
// import '@/assets/styles.scss';
import {createApp, DefineComponent, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';

/* import font awesome icon component */
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {fas} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import {ZiggyVue} from 'ziggy-js';
import {usePrimeVueLocalePL} from '@advance-table/PrimeVueLocale';
import Aura from '@primevue/themes/aura';

import Tooltip from 'primevue/tooltip';
import DialogService from 'primevue/dialogservice';

import {createPinia} from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
// import OwnDataTable from '@/../../packages/karlos3098/laravel-primevue-table-service/src/Assets/PrimeVue/4.0.5/OwnDataTable.vue';

library.add(fas);
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

// Sentry.init({
//     dsn: import.meta.env.VITE_SENTRY_DSN_PUBLIC,
// });

import ToolTipDirective from '@/tooltips/module'

createInertiaApp({
    progress: {
        // The delay after which the progress bar will appear, in milliseconds...
        delay: 250,

        // The color of the progress bar...
        color: '#29d',

        // Whether to include the default NProgress styles...
        includeCSS: true,

        // Whether the NProgress spinner will be shown...
        showSpinner: true,
    },
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        createApp({render: () => h(App, props)})
            .use(plugin)
            .use(ZiggyVue) // /** , Ziggy * /

            // .use(router)

            .component('font-awesome-icon', FontAwesomeIcon)

            .use(PrimeVue, {
                ...usePrimeVueLocalePL({}),
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.app-dark'
                    }
                }
            })
            .use(ToastService)
            .use(ConfirmationService)
            .use(DialogService)
            .directive('tooltip', Tooltip)

            // .component('BlockViewer', BlockViewer)

            .use(pinia)

            .directive('tip', ToolTipDirective)

            // .component('OwnDataTable', OwnDataTable)

            .mount(el);
    },
}).then((_) => {
});
