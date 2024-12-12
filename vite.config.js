import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { fileURLToPath, URL } from 'node:url';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';
export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue(),
        Components({
            resolvers: [
                PrimeVueResolver(),
            ]
        })
    ],
    resolve: {
        alias: {
            // '/demo': fileURLToPath(new URL('/public/demo', import.meta.url)),
            // '/layout': fileURLToPath(new URL('/public/layout', import.meta.url)),
            // '@advance-table': fileURLToPath(new URL('/packages/karlos3098/laravel-primevue-table-service/src/Assets', import.meta.url)),
            // '@advance-table-primevue-dir': fileURLToPath(new URL('/packages/karlos3098/laravel-primevue-table-service/src/Assets/PrimeVue/4.0.5', import.meta.url)),
        }
    }
});
