import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appHost = (() => {
        try { return new URL(env.APP_URL || 'http://localhost').hostname; }
        catch { return 'localhost'; }
    })();

    return {
        server: {
            host: '0.0.0.0',
            hmr: { host: appHost },
        },
        build: {
            // mapbox-gl is inherently large; keep it isolated and avoid noisy warnings.
            chunkSizeWarningLimit: 1800,
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        if (id.includes('node_modules/mapbox-gl')) {
                            return 'mapbox-gl';
                        }

                        if (id.includes('node_modules')) {
                            return 'vendor';
                        }
                    },
                },
            },
        },
        plugins: [
            laravel({
                input: ['resources/js/app.ts'],
                ssr: 'resources/js/ssr.ts',
                refresh: true,
            }),
            tailwindcss(),
            wayfinder({
                formVariants: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
    };
});
