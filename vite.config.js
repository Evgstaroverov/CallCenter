import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue'; // Добавили эту строку

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),

 	vue(), // И добавили эту строку

        tailwindcss(),
    ],


    resolve: { // Добавь этот блок
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },


    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
});
