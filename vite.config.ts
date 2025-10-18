import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import path from 'node:path';

export default defineConfig(({ mode }) => ({
    plugins: [react()],
    base: mode === 'development' ? '/' : '/build/',
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
    },
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'assets/app.jsx'),
            },
        },
    },
    publicDir: false,
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'assets'),
        },
    },
}));
