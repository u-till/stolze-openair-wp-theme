import { defineConfig } from 'vite';
import { resolve } from 'path';

// Single entry (src/js/app.js) which imports the SCSS design system.
// Produces dist/ + dist/.vite/manifest.json consumed by functions.php.
export default defineConfig({
    base: '',
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: resolve(__dirname, 'src/js/app.js'),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                // Allow `@use 'styles/...'` style imports from src/scss.
                loadPaths: [resolve(__dirname, 'src')],
            },
        },
    },
});
