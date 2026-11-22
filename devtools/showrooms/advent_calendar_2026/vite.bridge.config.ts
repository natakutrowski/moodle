import {defineConfig} from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import {fileURLToPath, URL} from 'node:url';
export default defineConfig({
  base:'./',
  plugins:[react(),tailwindcss()],
  resolve:{alias:{'@':fileURLToPath(new URL('./src',import.meta.url))}},
  build:{outDir:'dist',emptyOutDir:true,assetsInlineLimit:0,cssCodeSplit:false,sourcemap:false,
    rollupOptions:{input:fileURLToPath(new URL('./src/bridge/entry.tsx',import.meta.url)),
      output:{format:'es',entryFileNames:'main.js',chunkFileNames:'assets/[name]-[hash].js',
        assetFileNames:asset=>asset.name?.endsWith('.css')?'style.css':'assets/[name]-[hash][extname]'}}}
});
