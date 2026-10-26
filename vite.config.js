import { defineConfig } from 'vite';
import { resolve } from 'node:path';

/**
 * Bundle the Nyumbani formatting helpers into resources/dist/helpers.js, with pluralize inside,
 * so an app imports one file and installs no npm package.
 */
export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        lib: {
            entry: { helpers: resolve(import.meta.dirname, 'resources/js/helpers.js') },
            formats: ['es'],
            fileName: (format, name) => `${name}.js`,
        },
    },
});
