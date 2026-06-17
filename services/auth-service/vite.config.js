// Configuración de Vite para el auth-service
// Compila los assets CSS/JS del servicio (Tailwind + Alpine.js si aplica)
// y habilita recarga en caliente (HMR) durante desarrollo.
//
// El auth-service solo renderiza la vista welcome.blade.php,
// el resto de la interacción es vía API desde el frontend SPA.

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
