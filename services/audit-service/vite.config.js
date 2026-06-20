// Configuración de Vite para el audit-service
// Compila los assets CSS/JS del servicio (Tailwind + Alpine.js si aplica)
// y habilita recarga en caliente (HMR) durante desarrollo.
//
// El audit-service solo expone endpoints JSON y una vista welcome
// básica, por lo que los assets compilados son mínimos.

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
