/** Standalone browser ESM: no Lovable SSR/plugin, images remain separate files. */
import {defineConfig} from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import {fileURLToPath, URL} from 'node:url';
export default defineConfig({
  base: './',
  plugins: [react(), tailwindcss()],
  resolve: {alias: {'@': fileURLToPath(new URL('./src', import.meta.url))}},
  build: {
    outDir: fileURLToPath(new URL(process.env.CAMPUSFR_BRIDGE_PREVIEW === '1' ? '../draft/__CAMPUSFR_DESIGN_ID__/dist' : '../ready/__CAMPUSFR_DESIGN_ID__/dist', import.meta.url)),
    emptyOutDir: true,
    assetsInlineLimit: 0,
    cssCodeSplit: false,
    rollupOptions: {
      input: fileURLToPath(new URL('./src/bridge/campusfr-entry.tsx', import.meta.url)),
      output: {
        format: 'es', entryFileNames: 'main.js', chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: asset => asset.name?.endsWith('.css') ? 'style.css' : 'assets/[name]-[hash][extname]'
      }
    }
  }
});
