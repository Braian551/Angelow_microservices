// Configuración de Vite para el cart-service
// Compila los assets CSS/JS del servicio (Tailwind + Alpine.js si aplica)
// y habilita recarga en caliente (HMR) durante desarrollo.
//
// El cart-service es principalmente API; los assets compilados solo
// aplican a la vista welcome.blade.php.

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
