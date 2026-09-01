import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  build: {
    // Génère le manifest nécessaire à la lecture par PHP en production
    manifest: true,
    rollupOptions: {
      // Entrée principale pour tes assets
      input: 'assets/js/index.js',
    },
    outDir: 'dist',
  },
});
