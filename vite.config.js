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
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('apexcharts')) {
                        return 'vendor-charts';
                    }

                    if (id.includes('sweetalert2')) {
                        return 'vendor-alerts';
                    }

                    if (id.includes('simple-datatables')) {
                        return 'vendor-tables';
                    }

                    if (id.includes('glightbox')) {
                        return 'vendor-lightbox';
                    }
                },
            },
        },
        // ApexCharts minifies to ~580 kB; it is lazy-loaded on the admin dashboard only.
        chunkSizeWarningLimit: 650,
    },
});
