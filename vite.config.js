import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  // Evite de recopier automatiquement le dossier public vers dist
  publicDir: false,
  build: {
    // Génère le manifest nécessaire à la lecture par PHP en production
    manifest: true,
    copyPublicDir: false,
    rollupOptions: {
      // Entrée principale pour tes assets
      input: 'assets/js/index.js',
    },
    outDir: 'public/dist',
  },
});
