import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { fileURLToPath, URL } from 'node:url';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import vueDevTools from 'vite-plugin-vue-devtools';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue(),
        vueDevTools({
            appendTo: 'body'
        }),
        Components({
            resolvers: [
                PrimeVueResolver(),
            ]
        })
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '/demo': fileURLToPath(new URL('/public/demo', import.meta.url)),
            '/layout': fileURLToPath(new URL('/public/layout', import.meta.url)),
            '@advance-table': fileURLToPath(new URL('./resources/js/karlos3098-LaravelPrimevueTable/', import.meta.url)),
            '@advance-table-primevue-dir': fileURLToPath(new URL('./resources/js/karlos3098-LaravelPrimevueTable/PrimeVue/4.0.5', import.meta.url)),
        }
    }
});
