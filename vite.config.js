import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

// Get DDEV primary URL or fallback to localhost
const ddevUrl = process.env.DDEV_PRIMARY_URL || 'https://vehicles-fleet.ddev.site';
const ddevUrlWithoutPort = ddevUrl.replace(/^https?:\/\//, '').split(':')[0];
const viteOrigin = `https://${ddevUrlWithoutPort}:5173`;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: viteOrigin,
        cors: {
            origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
            credentials: true,
        },
        hmr: {
            protocol: 'wss',
            host: ddevUrlWithoutPort,
            port: 5173,
        },
    },
});
