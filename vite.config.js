import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

// Build the Vite entry list from the frontend themes that actually exist on
// disk. This snapshot only ships a subset of the production themes, so we
// include each `frontends/<theme>/{css,js}` entry only when its file is present.
const frontendEntries = [];
const frontendsDir = path.resolve(__dirname, 'frontends');
if (fs.existsSync(frontendsDir)) {
    for (const theme of fs.readdirSync(frontendsDir)) {
        if (theme.startsWith('_')) continue; // skip _core and similar shared dirs
        for (const rel of ['css/app.css', 'css/home.css', 'js/app.js']) {
            const entry = `frontends/${theme}/${rel}`;
            if (fs.existsSync(path.join(frontendsDir, theme, rel))) {
                frontendEntries.push(entry);
            }
        }
    }
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                ...frontendEntries,

                // Admin (unchanged)
                'resources/js/admin-editor.js',

                // Legacy — keep for admin/account pages that still use it
                'resources/css/critical.css',
                'resources/css/app.css',
                'resources/js/app.js',
            ],
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
