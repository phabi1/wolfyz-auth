import { fileURLToPath } from 'node:url';
import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';

const projectDir = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, projectDir, 'VITE_');
  const devServer = new URL(env.VITE_DEV_SERVER_URL || 'http://localhost:5173');
  const appOrigin = env.VITE_APP_ORIGIN || 'http://localhost';
  const secure = devServer.protocol === 'https:';

  return {
    plugins: [tailwindcss()],
    publicDir: false,
    base: mode === 'production' ? '/dist/' : '/',
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      origin: devServer.origin,
      allowedHosts: [devServer.hostname],
      cors: { origin: appOrigin },
      hmr: {
        protocol: secure ? 'wss' : 'ws',
        host: devServer.hostname,
        clientPort: Number(devServer.port || (secure ? 443 : 80)),
      },
      watch: {
        usePolling: env.VITE_USE_POLLING === 'true',
      },
      fs: {
        allow: [
          fileURLToPath(new URL('./assets', import.meta.url)),
          fileURLToPath(new URL('./node_modules', import.meta.url)),
        ],
      },
    },
    build: {
      outDir: 'public/dist',
      manifest: 'manifest.json',
      rolldownOptions: {
        input: fileURLToPath(new URL('./assets/js/index.js', import.meta.url)),
      },
    },
  };
});
